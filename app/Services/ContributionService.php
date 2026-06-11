<?php
namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\Cpf\CpfContribution;
use App\Models\Cpf\CpfContributionBatch;
use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Support\FiscalYearService;
use App\Support\MoneyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContributionService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Generate a DRAFT contribution batch for a month/year and pre-populate
     * one row per active employee from their current basic salary.
     * Nothing is posted to the ledger here.
     */
    public function generateBatch(int $month, int $year, int $createdBy): CpfContributionBatch
    {
        return DB::transaction(function () use ($month, $year, $createdBy) {
            $contributionMonth = Carbon::create($year, $month, 1);

            $batch = CpfContributionBatch::create([
                'contribution_month' => $contributionMonth,
                'fiscal_year'        => FiscalYearService::fromDate($contributionMonth),
                'status'             => BatchStatus::DRAFT,
                'created_by'         => $createdBy,
            ]);

            $this->populateRows($batch);

            return $batch->fresh('contributions');
        });
    }

    /**
     * Regenerate a DRAFT batch.
     *
     * Wipes the existing rows and re-creates them from current salaries —
     * used by the officer when the auto-draft is stale or wrong. Only DRAFT
     * batches can be regenerated, and any manual edits are discarded.
     */
    public function regenerateBatch(CpfContributionBatch $batch): CpfContributionBatch
    {
        if (! $batch->isEditable()) {
            throw new \RuntimeException('Only draft batches can be regenerated.');
        }

        return DB::transaction(function () use ($batch) {
            $batch->contributions()->delete();
            $this->populateRows($batch);

            return $batch->fresh('contributions');
        });
    }

    /**
     * Shared row builder for generate/regenerate.
     */
    protected function populateRows(CpfContributionBatch $batch): void
    {
        $employeeRate   = Setting::employeeContributionRate();
        $governmentRate = Setting::governmentContributionRate();

        // Snapshot the rates actually applied, so a later Settings change
        // never rewrites the percentages shown on historical batches.
        $batch->update([
            'employee_rate'   => $employeeRate,
            'government_rate' => $governmentRate,
        ]);

        Employee::query()
            ->active()
            ->with('payScaleStep')
            ->chunk(100, function ($employees) use ($batch, $employeeRate, $governmentRate) {
                foreach ($employees as $employee) {
                    $basicSalary = $employee->current_basic_salary;

                    if (! $basicSalary) {
                        continue;
                    }

                    CpfContribution::create([
                        'cpf_contribution_batch_id' => $batch->id,
                        'employee_id'               => $employee->id,
                        'basic_salary'              => $basicSalary,
                        'employee_contribution'     => MoneyService::percentage($basicSalary, $employeeRate),
                        'government_contribution'   => MoneyService::percentage($basicSalary, $governmentRate),
                    ]);
                }
            });
    }

    /**
     * Officer manually edits a single DRAFT row (e.g. correcting a salary or
     * a contribution amount before submitting for approval).
     */
    public function updateContribution(CpfContribution $contribution, array $data): CpfContribution
    {
        if (! $contribution->batch->isEditable()) {
            throw new \RuntimeException('Contributions can only be edited while the batch is in draft.');
        }

        $contribution->update([
            'basic_salary'            => $data['basic_salary'] ?? $contribution->basic_salary,
            'employee_contribution'   => $data['employee_contribution'] ?? $contribution->employee_contribution,
            'government_contribution' => $data['government_contribution'] ?? $contribution->government_contribution,
            'remarks'                 => $data['remarks'] ?? $contribution->remarks,
        ]);

        return $contribution->fresh();
    }

    /**
     * Officer submits a DRAFT batch for admin approval.
     * Locks the batch; no ledger entries yet.
     */
    public function submitBatch(CpfContributionBatch $batch, int $submittedBy): void
    {
        if (! $batch->canBeSubmitted()) {
            throw new \RuntimeException('Only draft batches can be submitted for approval.');
        }

        if ($batch->employeeCount() === 0) {
            throw new \RuntimeException('Cannot submit an empty batch. Regenerate or add contributions first.');
        }

        $batch->update([
            'status'       => BatchStatus::SUBMITTED,
            'submitted_by' => $submittedBy,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Admin approves a SUBMITTED batch.
     *
     * Posts two credit ledger entries (employee + government) per contribution
     * and transitions the batch to APPROVED. This is the point of record.
     */
    public function approveBatch(CpfContributionBatch $batch, int $approvedBy): void
    {
        if (! $batch->canBeApproved()) {
            throw new \RuntimeException('Only batches pending approval can be approved.');
        }

        DB::transaction(function () use ($batch, $approvedBy) {
            $batch->load('contributions');

            foreach ($batch->contributions as $contribution) {
                $this->postContribution($contribution, $approvedBy);
            }

            $batch->update([
                'status'      => BatchStatus::APPROVED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Admin sends a SUBMITTED batch back to the officer (DRAFT) for correction.
     */
    public function rejectBatch(CpfContributionBatch $batch, ?string $remarks = null): void
    {
        if (! $batch->canBeRejected()) {
            throw new \RuntimeException('Only batches pending approval can be sent back.');
        }

        $batch->update([
            'status'       => BatchStatus::DRAFT,
            'submitted_by' => null,
            'submitted_at' => null,
            'remarks'      => $remarks,
        ]);
    }

    /**
     * Post a single contribution as two credit ledger entries.
     */
    protected function postContribution(CpfContribution $contribution, int $createdBy): void
    {
        $batch           = $contribution->batch;
        $transactionDate = $batch->contribution_month;
        $monthLabel      = $batch->contribution_month->format('F Y');   // e.g. "July 2026"
        $referenceNo     = 'CPF-CON-' . $transactionDate->format('Ym'); // e.g. CPF-CON-202607

        // Employee contribution (credit)
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $transactionDate,
            'transaction_type' => LedgerTransactionType::EMPLOYEE_CONTRIBUTION,
            'source_type'      => SourceType::CONTRIBUTION->value,
            'source_id'        => $contribution->id,
            'reference_no'     => $referenceNo,
            'remarks'          => "Employee CPF contribution for {$monthLabel} (Batch #{$batch->id})",
            'debit'      => 0,
            'credit'     => $contribution->employee_contribution,
            'created_by' => $createdBy,
        ]);

        // Government contribution (credit)
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $transactionDate,
            'transaction_type' => LedgerTransactionType::GOVERNMENT_CONTRIBUTION,
            'source_type'      => SourceType::CONTRIBUTION->value,
            'source_id'        => $contribution->id,
            'reference_no'     => $referenceNo,
            'remarks'          => "Government CPF contribution for {$monthLabel} (Batch #{$batch->id})",
            'debit' => 0,
            'credit' => $contribution->government_contribution,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Reverse an APPROVED batch.
     *
     * Posts mirror-image debit entries through LedgerService so running
     * balances unwind correctly, then marks the batch REVERSED. Note: balance
     * is an unsigned column — reversing after the member has spent the funds
     * (e.g. via an advance) can underflow; guard upstream if that's possible.
     */
    public function reverseBatch(CpfContributionBatch $batch, int $reversedBy): void
    {
        if (! $batch->canBeReversed()) {
            throw new \RuntimeException('Only approved batches can be reversed.');
        }

        DB::transaction(function () use ($batch, $reversedBy) {
            $batch->load('contributions');

            $monthLabel  = $batch->contribution_month->format('F Y');
            $referenceNo = 'CPF-REV-' . $batch->contribution_month->format('Ym');
            $reverseDate = today();

            foreach ($batch->contributions as $contribution) {
                // Reverse employee contribution (debit)
                $this->ledgerService->create([
                    'employee_id'      => $contribution->employee_id,
                    'transaction_date' => $reverseDate,
                    'transaction_type' => LedgerTransactionType::EMPLOYEE_CONTRIBUTION,
                    'source_type'      => SourceType::CONTRIBUTION->value,
                    'source_id'        => $contribution->id,
                    'reference_no'     => $referenceNo,
                    'remarks'          => "Reversal of employee CPF contribution for {$monthLabel} (Batch #{$batch->id})",
                    'debit'      => $contribution->employee_contribution,
                    'credit'     => 0,
                    'created_by' => $reversedBy,
                ]);

                // Reverse government contribution (debit)
                $this->ledgerService->create([
                    'employee_id'      => $contribution->employee_id,
                    'transaction_date' => $reverseDate,
                    'transaction_type' => LedgerTransactionType::GOVERNMENT_CONTRIBUTION,
                    'source_type'      => SourceType::CONTRIBUTION->value,
                    'source_id'        => $contribution->id,
                    'reference_no'     => $referenceNo,
                    'remarks'          => "Reversal of government CPF contribution for {$monthLabel} (Batch #{$batch->id})",
                    'debit'      => $contribution->government_contribution,
                    'credit'     => 0,
                    'created_by' => $reversedBy,
                ]);
            }

            $batch->update([
                'status'      => BatchStatus::REVERSED,
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
            ]);
        });
    }
}
