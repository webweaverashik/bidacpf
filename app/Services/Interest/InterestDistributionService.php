<?php
namespace App\Services\Interest;

use App\Enums\BatchStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\Employee\Employee;
use App\Models\Interest\BankInterestBatch;
use App\Models\Interest\BankInterestDistribution;
use App\Services\Cpf\LedgerService;
use App\Support\FiscalYearService;
use App\Support\MoneyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bank Interest Distribution lifecycle.
 *
 * Bi-annual (30 Jun / 31 Dec) distribution of bank interest received on the
 * CPF account, pro-rated across members by their CPF balance on the cut-off
 * date:
 *
 *   Employee Interest = (Employee CPF Balance / Total CPF Fund Balance)
 *                       x Total Bank Interest Received
 *
 * Mirrors ContributionService: createBatch / regenerate (DRAFT) ->
 * submitBatch (SUBMITTED) -> approveBatch (APPROVED, posts ledger) |
 * rejectBatch (back to DRAFT) -> reverseBatch (REVERSED, posts debits).
 *
 * All amounts use the CPF half-up rounding policy. Ledger entries always
 * flow through LedgerService — never inserted directly.
 */
class InterestDistributionService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /*
    |--------------------------------------------------------------------------
    | DRAFT: create + (re)compute the preview distribution
    |--------------------------------------------------------------------------
    */

    /**
     * Create a DRAFT batch and immediately compute the per-member preview
     * distribution against the cut-off date balances. Nothing is posted to
     * the ledger here — the officer reviews first.
     *
     * @param array{distribution_date:string, fiscal_year?:string, total_interest_amount:int|string, remarks?:string|null} $data
     */
    public function createBatch(array $data, int $createdBy): BankInterestBatch
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $cutOff = Carbon::parse($data['distribution_date'])->startOfDay();

            $batch = BankInterestBatch::create([
                'distribution_date'      => $cutOff,
                'fiscal_year'            => $data['fiscal_year'] ?? FiscalYearService::fromDate($cutOff),
                'total_interest_amount'  => (int) $data['total_interest_amount'],
                'total_eligible_balance' => 0,
                'status'                 => BatchStatus::DRAFT,
                'remarks'                => $data['remarks'] ?? null,
                'created_by'             => $createdBy,
            ]);

            $this->computeDistributions($batch);

            return $batch->fresh('distributions');
        });
    }

    /**
     * Recompute a DRAFT batch's distribution from current cut-off balances.
     * Used when balances changed since the draft was generated. Only DRAFT
     * batches can be regenerated.
     */
    public function regenerate(BankInterestBatch $batch): BankInterestBatch
    {
        if (! $batch->isEditable()) {
            throw new \RuntimeException('Only draft interest batches can be regenerated.');
        }

        return DB::transaction(function () use ($batch) {
            $this->computeDistributions($batch);

            return $batch->fresh('distributions');
        });
    }

    /**
     * Shared distribution builder.
     *
     * 1. Reads every active member's CPF balance as of the cut-off date.
     * 2. Members with a zero or negative balance are excluded.
     * 3. Each member's share is pro-rated and half-up rounded.
     * 4. A full calculation snapshot is stored per row for audit.
     */
    protected function computeDistributions(BankInterestBatch $batch): void
    {
        // Start clean so re-generating a draft is idempotent.
        $batch->distributions()->delete();

        $totalInterest = (int) $batch->total_interest_amount;
        $cutOffDate    = $batch->distribution_date;
        $cutOffString  = $cutOffDate->toDateString();

        // --- 1 & 2: collect eligible balances ----------------------------
        $balances         = [];
        $totalFundBalance = 0;

        Employee::query()
            ->active()
            ->chunk(200, function ($employees) use (&$balances, &$totalFundBalance, $cutOffDate) {
                foreach ($employees as $employee) {
                    $balance = $this->ledgerService->balanceAsOf($employee->id, $cutOffDate);

                    if ($balance <= 0) {
                        continue; // exclude zero / negative balances
                    }

                    $balances[$employee->id] = $balance;
                    $totalFundBalance       += $balance;
                }
            });

        $batch->update(['total_eligible_balance' => $totalFundBalance]);

        // Nothing to distribute against.
        if ($totalFundBalance <= 0) {
            return;
        }

        // --- 3 & 4: pro-rate, round, snapshot ----------------------------
        $rows = [];
        $now  = now();

        foreach ($balances as $employeeId => $eligibleBalance) {
            $ratio      = $eligibleBalance / $totalFundBalance;
            $calculated = $totalInterest * $ratio;
            $rounded    = MoneyService::round($calculated);

            $rows[] = [
                'bank_interest_batch_id' => $batch->id,
                'employee_id'            => $employeeId,
                'eligible_balance'       => $eligibleBalance,
                'interest_amount'        => $rounded,
                'calculation_snapshot'   => json_encode([
                    'cut_off_date'        => $cutOffString,
                    'employee_balance'    => $eligibleBalance,
                    'total_fund_balance'  => $totalFundBalance,
                    'total_interest'      => $totalInterest,
                    'ratio'               => round($ratio, 12),
                    'calculated_interest' => round($calculated, 4),
                    'rounded_interest'    => $rounded,
                    'rounding_policy'     => 'HALF_UP',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk insert for performance on large member counts.
        BankInterestDistribution::insert($rows);
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow transitions
    |--------------------------------------------------------------------------
    */

    /**
     * Officer submits a DRAFT batch for admin approval.
     * Locks the batch; no ledger entries yet.
     */
    public function submitBatch(BankInterestBatch $batch, int $submittedBy): void
    {
        if (! $batch->canBeSubmitted()) {
            throw new \RuntimeException('Only draft interest batches can be submitted for approval.');
        }

        if ($batch->distributionCount() === 0) {
            throw new \RuntimeException('Cannot submit an empty distribution. Regenerate the batch first.');
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
     * Posts one BANK_INTEREST credit per member and transitions the batch to
     * APPROVED. This is the point of record — balances increase here.
     */
    public function approveBatch(BankInterestBatch $batch, int $approvedBy): void
    {
        if (! $batch->canBeApproved()) {
            throw new \RuntimeException('Only batches pending approval can be approved.');
        }

        DB::transaction(function () use ($batch, $approvedBy) {
            $batch->load('distributions');

            $cutOff      = $batch->distribution_date->format('d M Y');
            $referenceNo = $batch->reference_no; // CPF-INT-YYYYMMDD

            foreach ($batch->distributions as $distribution) {
                // Skip zero-interest rows (rounding edge cases).
                if ($distribution->interest_amount <= 0) {
                    continue;
                }

                $this->ledgerService->create([
                    'employee_id'      => $distribution->employee_id,
                    'transaction_date' => $batch->distribution_date,
                    'transaction_type' => LedgerTransactionType::BANK_INTEREST,
                    'source_type'      => SourceType::INTEREST_DISTRIBUTION->value,
                    'source_id'        => $distribution->id,
                    'reference_no'     => $referenceNo,
                    'remarks'          => "Bank interest distribution as of {$cutOff} (Batch #{$batch->id})",
                    'debit'            => 0,
                    'credit'           => $distribution->interest_amount,
                    'created_by'       => $approvedBy,
                ]);
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
    public function rejectBatch(BankInterestBatch $batch, ?string $remarks = null): void
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
     * Reverse an APPROVED batch.
     *
     * Posts mirror-image debit entries through LedgerService so running
     * balances unwind correctly, then marks the batch REVERSED.
     */
    public function reverseBatch(BankInterestBatch $batch, int $reversedBy): void
    {
        if (! $batch->canBeReversed()) {
            throw new \RuntimeException('Only approved interest batches can be reversed.');
        }

        DB::transaction(function () use ($batch, $reversedBy) {
            $batch->load('distributions');

            $cutOff      = $batch->distribution_date->format('d M Y');
            $referenceNo = 'CPF-INT-REV-' . $batch->distribution_date->format('Ymd');
            $reverseDate = today();

            foreach ($batch->distributions as $distribution) {
                if ($distribution->interest_amount <= 0) {
                    continue;
                }

                $this->ledgerService->create([
                    'employee_id'      => $distribution->employee_id,
                    'transaction_date' => $reverseDate,
                    'transaction_type' => LedgerTransactionType::BANK_INTEREST,
                    'source_type'      => SourceType::INTEREST_DISTRIBUTION->value,
                    'source_id'        => $distribution->id,
                    'reference_no'     => $referenceNo,
                    'remarks'          => "Reversal of bank interest distribution as of {$cutOff} (Batch #{$batch->id})",
                    'debit'            => $distribution->interest_amount,
                    'credit'           => 0,
                    'created_by'       => $reversedBy,
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
