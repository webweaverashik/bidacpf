<?php
namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\CpfContribution;
use App\Models\CpfContributionBatch;
use App\Models\Employee;
use App\Models\Setting;
use App\Support\FiscalYearService;
use App\Support\MoneyService;
use Illuminate\Support\Facades\DB;

class ContributionService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Generate monthly contribution batch.
     */
    public function generateBatch(int $month, int $year, int $createdBy): CpfContributionBatch
    {
        return DB::transaction(function () use ($month, $year, $createdBy) {
            $batch = CpfContributionBatch::create([
                'month'       => $month,
                'year'        => $year,
                'fiscal_year' => FiscalYearService::fromDate(now()->setMonth($month)->setYear($year)),
                'status'      => BatchStatus::DRAFT,
                'created_by'  => $createdBy,
            ]);

            Employee::query()
                ->active()
                ->with('payScaleStep')
                ->chunk(100, function ($employees) use ($batch) {
                    foreach ($employees as $employee) {
                        $basicSalary = $employee->current_basic_salary;

                        $employeeContribution = MoneyService::percentage($basicSalary, Setting::employeeContributionRate());

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
     * Submit contribution batch.
     */
    public function submitBatch(CpfContributionBatch $batch, int $submittedBy): void
    {
        if ($batch->status !== BatchStatus::DRAFT) {
            throw new \Exception('Only draft batches can be submitted.');
        }

        DB::transaction(function () use ($batch, $submittedBy) {
            $batch->load('contributions');

            foreach ($batch->contributions as $contribution) {
                $this->submittedContribution($contribution, $submittedBy);
            }

            $batch->update([
                'status'       => BatchStatus::SUBMITTED,
                'submitted_by' => $submittedBy,
                'submitted_at' => now(),
            ]);
        });
    }

    /**
     * Post single contribution.
     */
    protected function submittedContribution(CpfContribution $contribution, int $createdBy): void
    {
        $batch = $contribution->batch;

        $referenceNo = sprintf('CPF-CON-%04d%02d', $batch->year, $batch->month);

        /**
         * Employee Contribution
         */
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $batch->posting_date ?? now(),
            'transaction_type' => LedgerTransactionType::EMPLOYEE_CONTRIBUTION,
            'source_type'      => SourceType::CONTRIBUTION,
            'source_id'        => $contribution->id,
            'reference_no'     => $referenceNo,
            'remarks'          => 'Monthly Employee CPF Contribution',
            'debit'            => 0,
            'credit'           => $contribution->employee_contribution,
            'created_by'       => $createdBy,
        ]);

        /**
         * Government Contribution
         */
        $this->ledgerService->create([
            'employee_id'      => $contribution->employee_id,
            'transaction_date' => $batch->posting_date ?? now(),
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
     * Reverse batch.
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

            // Future implementation:
            // Reverse ledger entries.
        });
    }
}
