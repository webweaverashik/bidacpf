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
     * Generate monthly contribution batch.
     *
     * Creates a DRAFT batch for the given month/year and pre-populates
     * contribution rows for every active employee based on their current
     * basic salary.  Nothing is posted to the ledger yet — that happens
     * in submitBatch().
     */
    public function generateBatch(int $month, int $year, int $createdBy): CpfContributionBatch
    {
        return DB::transaction(function () use ($month, $year, $createdBy) {

            // Fix 1: use contribution_month (date column), not separate month/year columns
            $contributionMonth = Carbon::create($year, $month, 1);

            $batch = CpfContributionBatch::create([
                'contribution_month' => $contributionMonth,
                'fiscal_year'        => FiscalYearService::fromDate($contributionMonth),
                'status'             => BatchStatus::DRAFT,
                'created_by'         => $createdBy,
            ]);

            Employee::query()
                ->active()
                ->with('payScaleStep')
                ->chunk(100, function ($employees) use ($batch) {
                    foreach ($employees as $employee) {
                        $basicSalary = $employee->current_basic_salary;

                        // Skip employees with no salary assigned yet
                        if (! $basicSalary) {
                            continue;
                        }

                        $employeeContribution   = MoneyService::percentage($basicSalary, Setting::employeeContributionRate());
                        $governmentContribution = MoneyService::percentage($basicSalary, Setting::governmentContributionRate());

                        CpfContribution::create([
                            'cpf_contribution_batch_id' => $batch->id,
                            'employee_id'               => $employee->id,
                            'basic_salary'              => $basicSalary,
                            'employee_contribution'     => $employeeContribution,
                            'government_contribution'   => $governmentContribution,
                        ]);
                    }
                });

            return $batch->fresh();
        });
    }

    /**
     * Submit a draft batch.
     *
     * Posts ledger entries for every contribution row and transitions the
     * batch to SUBMITTED.  Only DRAFT batches can be submitted.
     */
    public function submitBatch(CpfContributionBatch $batch, int $submittedBy): void
    {
        if ($batch->status !== BatchStatus::DRAFT) {
            throw new \Exception('Only draft batches can be submitted.');
        }

        DB::transaction(function () use ($batch, $submittedBy) {
            $batch->load('contributions');

            foreach ($batch->contributions as $contribution) {
                $this->postContribution($contribution, $submittedBy);
            }

            $batch->update([
                'status'       => BatchStatus::SUBMITTED,
                'submitted_by' => $submittedBy, // requires column in migration — see note
                'submitted_at' => now(),
            ]);
        });
    }

    /**
     * Post a single contribution to the ledger.
     *
     * Creates two separate ledger entries per employee:
     *  - Employee contribution  (credit)
     *  - Government contribution (credit)
     */
    protected function postContribution(CpfContribution $contribution, int $createdBy): void
    {
        $batch = $contribution->batch;

        // Fix 2: use contribution_month (the actual date column) instead of posting_date (non-existent)
        $transactionDate = $batch->contribution_month;

        // Reference number format: CPF-CON-YYYYMM  e.g. CPF-CON-202607
        $referenceNo = 'CPF-CON-' . $transactionDate->format('Ym');

        // Employee Contribution
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $transactionDate,
            'transaction_type' => LedgerTransactionType::EMPLOYEE_CONTRIBUTION,
            'source_type'      => SourceType::CONTRIBUTION,
            'source_id'        => $contribution->id,
            'reference_no'     => $referenceNo,
            'remarks'          => 'Monthly Employee CPF Contribution',
            'debit'            => 0,
            'credit'           => $contribution->employee_contribution,
            'created_by'       => $createdBy,
        ]);

        // Government Contribution
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $transactionDate,
            'transaction_type' => LedgerTransactionType::GOVERNMENT_CONTRIBUTION,
            'source_type'      => SourceType::CONTRIBUTION,
            'source_id'        => $contribution->id,
            'reference_no'     => $referenceNo,
            'remarks'          => 'Monthly Government CPF Contribution',
            'debit'            => 0,
            'credit'           => $contribution->government_contribution,
            'created_by'       => $createdBy,
        ]);
    }

    /**
     * Reverse a submitted batch.
     *
     * Transitions the batch to REVERSED.  Ledger reversal entries are
     * intentionally deferred to a future implementation so the audit trail
     * can be reviewed before reversal entries are written.
     *
     * Only SUBMITTED batches can be reversed.
     */
    public function reverseBatch(CpfContributionBatch $batch): void
    {
        if ($batch->status !== BatchStatus::SUBMITTED) {
            throw new \Exception('Only submitted batches can be reversed.');
        }

        DB::transaction(function () use ($batch) {
            $batch->update([
                'status' => BatchStatus::REVERSED,
            ]);

            // TODO: create reversal ledger entries for each contribution.
        });
    }
}
