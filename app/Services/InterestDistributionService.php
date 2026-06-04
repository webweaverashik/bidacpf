<?php
namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\BankInterestBatch;
use App\Models\BankInterestDistribution;
use App\Models\Employee;
use App\Support\MoneyService;
use Illuminate\Support\Facades\DB;

class InterestDistributionService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Generate annual interest distribution.
     */
    public function generate(BankInterestBatch $batch, int $createdBy): BankInterestBatch
    {
        return DB::transaction(function () use ($batch, $createdBy) {
            $employees = Employee::active()->get();

            $totalEligibleBalance = 0;

            $balances = [];

            foreach ($employees as $employee) {
                $balance = $this->ledgerService->currentBalance($employee->id);

                $balances[$employee->id] = $balance;

                $totalEligibleBalance += $balance;
            }

            $batch->update([
                'total_eligible_balance' => $totalEligibleBalance,
            ]);

            foreach ($employees as $employee) {
                $eligibleBalance = $balances[$employee->id];

                if ($eligibleBalance <= 0) {
                    continue;
                }

                $ratio = $eligibleBalance / $totalEligibleBalance;

                $interest = MoneyService::round($batch->total_interest_amount * $ratio);

                BankInterestDistribution::create([
                    'bank_interest_batch_id' => $batch->id,
                    'employee_id'            => $employee->id,
                    'eligible_balance'       => $eligibleBalance,
                    'interest_amount'        => $interest,
                    'calculation_snapshot'   => [
                        'total_interest_amount'  => $batch->total_interest_amount,
                        'total_eligible_balance' => $totalEligibleBalance,
                        'eligible_balance'       => $eligibleBalance,
                        'ratio'                  => $ratio,
                        'rounding_policy'        => 'HALF_UP',
                    ],
                ]);
            }

            return $batch->fresh();
        });
    }

    /**
     * Submit interest batch.
     */
    public function submit(BankInterestBatch $batch, int $createdBy): void
    {
        DB::transaction(function () use ($batch, $createdBy) {
            $batch->load('distributions');

            foreach ($batch->distributions as $distribution) {
                $this->ledgerService->create([
                    'employee_id'      => $distribution->employee_id,
                    'transaction_date' => $batch->distribution_date,
                    'transaction_type' => LedgerTransactionType::BANK_INTEREST,
                    'source_type'      => SourceType::INTEREST_DISTRIBUTION,
                    'source_id'        => $distribution->id,
                    'reference_no'     => 'INT-' . $batch->id,
                    'remarks'          => 'Annual CPF Interest Distribution',
                    'debit'            => 0,
                    'credit'           => $distribution->interest_amount,
                    'created_by'       => $createdBy,
                ]);
            }

            $batch->update([
                'status'       => BatchStatus::SUBMITTED,
            ]);
        });
    }
}
