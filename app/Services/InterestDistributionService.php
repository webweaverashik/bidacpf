<?php
namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\Employee\Employee;
use App\Models\Interest\BankInterestBatch;
use App\Support\MoneyService;
use Illuminate\Support\Facades\DB;

class InterestDistributionService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Generate distribution rows for a draft batch.
     *
     * For each active employee with a positive balance, calculates their
     * proportional share of the total interest amount and creates a
     * BankInterestDistribution record.  The batch's total_eligible_balance
     * is updated to reflect the actual sum used in the calculation.
     *
     * Nothing is posted to the ledger here — that happens in submit().
     */
    public function generate(BankInterestBatch $batch, int $createdBy): BankInterestBatch
    {
        return DB::transaction(function () use ($batch, $createdBy) {

            // Delete any previously generated (but not yet submitted) distributions
            // so re-generating a draft batch starts clean.
            $batch->distributions()->delete();

            $employees = Employee::active()->get();

            $totalEligibleBalance = 0;
            $balances             = [];

            foreach ($employees as $employee) {
                $balance = $this->ledgerService->currentBalance($employee->id);

                if ($balance <= 0) {
                    continue; // exclude employees with zero or negative balance
                }

                $balances[$employee->id]  = $balance;
                $totalEligibleBalance    += $balance;
            }

            // If no employee has a positive balance there is nothing to distribute.
            if ($totalEligibleBalance <= 0) {
                $batch->update(['total_eligible_balance' => 0]);

                return $batch->fresh();
            }

            $batch->update(['total_eligible_balance' => $totalEligibleBalance]);

            $rows = [];

            foreach ($balances as $employeeId => $eligibleBalance) {
                $ratio    = $eligibleBalance / $totalEligibleBalance;
                $interest = MoneyService::round($batch->total_interest_amount * $ratio);

                $rows[] = [
                    'bank_interest_batch_id' => $batch->id,
                    'employee_id'            => $employeeId,
                    'eligible_balance'       => $eligibleBalance,
                    'interest_amount'        => $interest,
                    'calculation_snapshot'   => json_encode([
                        'total_interest_amount'  => $batch->total_interest_amount,
                        'total_eligible_balance' => $totalEligibleBalance,
                        'eligible_balance'       => $eligibleBalance,
                        'ratio'                  => $ratio,
                        'posted_interest'        => $interest,
                        'rounding_policy'        => 'HALF_UP',
                    ]),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }

            // Bulk insert for performance (large employee counts)
            BankInterestDistribution::insert($rows);

            return $batch->fresh();
        });
    }

    /**
     * Submit a draft interest batch.
     *
     * Posts one BANK_INTEREST ledger credit per employee and transitions
     * the batch status to SUBMITTED.  Only DRAFT batches can be submitted.
     */
    public function submit(BankInterestBatch $batch, int $createdBy): void
    {
        if ($batch->status !== BatchStatus::DRAFT) {
            throw new \Exception('Only draft interest batches can be submitted.');
        }

        DB::transaction(function () use ($batch, $createdBy) {
            $batch->load('distributions');

            foreach ($batch->distributions as $distribution) {
                // Skip zero-interest rows (rounding edge cases)
                if ($distribution->interest_amount <= 0) {
                    continue;
                }

                $this->ledgerService->create([
                    'employee_id'      => $distribution->employee_id,
                    'transaction_date' => $batch->distribution_date,
                    'transaction_type' => LedgerTransactionType::BANK_INTEREST,
                    'source_type'      => SourceType::INTEREST_DISTRIBUTION,
                    'source_id'        => $distribution->id,
                    // Fix: use zero-padded batch ID so reference sorts predictably
                    'reference_no'     => sprintf('INT-%04d', $batch->id),
                    'remarks'          => 'Annual CPF Interest Distribution',
                    'debit'            => 0,
                    'credit'           => $distribution->interest_amount,
                    'created_by'       => $createdBy,
                ]);
            }

            $batch->update([
                'status'       => BatchStatus::SUBMITTED,
                'submitted_by' => $createdBy,
                'submitted_at' => now(),
            ]);
        });
    }

    /**
     * Reverse a submitted interest batch.
     *
     * Transitions status to REVERSED.  Ledger reversal entries are
     * deferred to a future implementation.
     *
     * Only SUBMITTED batches can be reversed.
     */
    public function reverse(BankInterestBatch $batch): void
    {
        if ($batch->status !== BatchStatus::SUBMITTED) {
            throw new \Exception('Only submitted interest batches can be reversed.');
        }

        DB::transaction(function () use ($batch) {
            $batch->update(['status' => BatchStatus::REVERSED]);

            // TODO: create reversal ledger entries for each distribution.
        });
    }
}
