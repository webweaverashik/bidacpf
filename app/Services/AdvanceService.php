<?php
namespace App\Services;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\RecoveryStatus;
use App\Enums\SourceType;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Support\MoneyService;
use Illuminate\Support\Facades\DB;

class AdvanceService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /*
    |--------------------------------------------------------------------------
    | Eligibility / limits
    |--------------------------------------------------------------------------
    */

    /**
     * Maximum advance an employee may take: a strictly-enforced percentage of
     * their current CPF balance (system setting; never customised per employee).
     */
    public function eligibleAmount(Employee $employee): int
    {
        return (int) floor($employee->currentBalance() * (Setting::advanceLimitPercentage() / 100));
    }

    /*
    |--------------------------------------------------------------------------
    | Advance lifecycle
    |--------------------------------------------------------------------------
    */

    /**
     * Create a DRAFT advance. Defaults rate & installment count from settings
     * unless the officer supplied policy-permitted overrides.
     */
    public function createDraft(array $data): CpfAdvance
    {
        return DB::transaction(function () use ($data) {
            return CpfAdvance::create([
                'advance_no'        => $this->generateAdvanceNo(),
                'employee_id'       => $data['employee_id'],
                'application_date'  => $data['application_date'],
                'requested_amount'  => $data['requested_amount'],
                'interest_rate'     => $data['interest_rate'] ?? Setting::advanceInterestRate(),
                'installment_count' => $data['installment_count'] ?? Setting::maxInstallments(),
                'remarks'           => $data['remarks'] ?? null,
                'status'            => AdvanceStatus::DRAFT,
                'created_by'        => $data['created_by'],
            ]);
        });
    }

    /**
     * Update editable fields on a DRAFT advance.
     */
    public function updateDraft(CpfAdvance $advance, array $data): CpfAdvance
    {
        if (! $advance->isEditable()) {
            throw new \RuntimeException('Only draft advances can be edited.');
        }

        $advance->update([
            'application_date'  => $data['application_date'] ?? $advance->application_date,
            'requested_amount'  => $data['requested_amount'] ?? $advance->requested_amount,
            'interest_rate'     => $data['interest_rate'] ?? $advance->interest_rate,
            'installment_count' => $data['installment_count'] ?? $advance->installment_count,
            'remarks'           => $data['remarks'] ?? $advance->remarks,
        ]);

        return $advance->fresh();
    }

    /**
     * Officer forwards a DRAFT to the admin for review. Requires an attachment.
     */
    public function submit(CpfAdvance $advance, int $submittedBy): CpfAdvance
    {
        if (! $advance->canSubmit()) {
            throw new \RuntimeException('Only draft advances can be submitted.');
        }

        if ($advance->attachments()->count() === 0) {
            throw new \RuntimeException('A scanned loan application must be attached before submitting.');
        }

        $advance->update([
            'status'       => AdvanceStatus::SUBMITTED,
            'submitted_by' => $submittedBy,
            'submitted_at' => now(),
            'reject_reason' => null,
        ]);

        return $advance->fresh();
    }

    /**
     * Admin approves a SUBMITTED advance.
     *
     * May override approved_amount / interest_rate / installment_count before
     * approval. Computes the interest benefit and per-installment amount, sets
     * the outstanding balance, and posts a DEBIT disbursement to the ledger.
     */
    public function approve(CpfAdvance $advance, array $overrides, int $approvedBy): CpfAdvance
    {
        if (! $advance->canApprove()) {
            throw new \RuntimeException('Only submitted advances can be approved.');
        }

        return DB::transaction(function () use ($advance, $overrides, $approvedBy) {
            $approvedAmount   = (int) ($overrides['approved_amount'] ?? $advance->requested_amount);
            $interestRate     = (float) ($overrides['interest_rate'] ?? $advance->interest_rate);
            $installmentCount = (int) ($overrides['installment_count'] ?? $advance->installment_count);

            $interestAmount = (int) MoneyService::percentage($approvedAmount, $interestRate);
            $totalPayable   = $approvedAmount + $interestAmount;

            // Per-installment covers the TOTAL repayable (principal + interest).
            $installmentAmount = $installmentCount > 0
                ? (int) ceil($totalPayable / $installmentCount)
                : $totalPayable;

            $advance->update([
                'status'                => AdvanceStatus::APPROVED,
                'approved_amount'       => $approvedAmount,
                'interest_rate'         => $interestRate,
                'interest_amount'       => $interestAmount,
                'installment_count'     => $installmentCount,
                'installment_amount'    => $installmentAmount,
                'principal_outstanding' => $approvedAmount,
                'interest_outstanding'  => $interestAmount,
                'outstanding_amount'    => $totalPayable,
                'approval_date'         => now(),
                'approved_by'           => $approvedBy,
            ]);

            // Disbursement reduces the available CPF balance by the principal (debit).
            $this->ledgerService->create([
                'employee_id'      => $advance->employee_id,
                'transaction_date' => now(),
                'transaction_type' => LedgerTransactionType::ADVANCE_DISBURSEMENT,
                'source_type'      => SourceType::ADVANCE->value,
                'source_id'        => $advance->id,
                'reference_no'     => $advance->advance_no,
                'remarks'          => "CPF Advance disbursement ({$advance->advance_no})",
                'debit'            => $approvedAmount,
                'credit'           => 0,
                'created_by'       => $approvedBy,
            ]);

            return $advance->fresh();
        });
    }

    /**
     * Admin rejects a SUBMITTED advance. No financial posting.
     */
    public function reject(CpfAdvance $advance, ?string $reason, int $rejectedBy): CpfAdvance
    {
        if (! $advance->canReject()) {
            throw new \RuntimeException('Only submitted advances can be rejected.');
        }

        $advance->update([
            'status'        => AdvanceStatus::REJECTED,
            'reject_reason' => $reason,
            'rejected_by'   => $rejectedBy,
            'rejected_at'   => now(),
        ]);

        return $advance->fresh();
    }

    /**
     * Recalculate the per-installment amount from the current outstanding
     * balance and (optionally) a new installment count. Used when the admin
     * changes the installment count on an approved, partially-repaid loan.
     */
    public function recalcInstallments(CpfAdvance $advance, ?int $newCount = null): CpfAdvance
    {
        if ($advance->status !== AdvanceStatus::APPROVED) {
            throw new \RuntimeException('Only approved advances can have their schedule recalculated.');
        }

        $count = $newCount ?? $advance->installment_count;

        $advance->update([
            'installment_count'  => $count,
            'installment_amount' => $count > 0
                ? (int) ceil($advance->outstanding_amount / $count)
                : $advance->outstanding_amount,
        ]);

        return $advance->fresh();
    }

    /**
     * Delete a DRAFT advance (and its attachments).
     */
    public function deleteDraft(CpfAdvance $advance, AttachmentService $attachments): void
    {
        if (! $advance->canDelete()) {
            throw new \RuntimeException('Only draft advances can be deleted.');
        }

        DB::transaction(function () use ($advance, $attachments) {
            $attachments->purge($advance);
            $advance->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery lifecycle
    |--------------------------------------------------------------------------
    */

    public function createRecoveryDraft(CpfAdvance $advance, array $data): CpfAdvanceRecovery
    {
        if (! $advance->canRecover()) {
            throw new \RuntimeException('Recoveries can only be recorded against an approved, outstanding advance.');
        }

        return DB::transaction(function () use ($advance, $data) {
            return CpfAdvanceRecovery::create([
                'recovery_no'       => $this->generateRecoveryNo(),
                'cpf_advance_id'    => $advance->id,
                'recovery_date'     => $data['recovery_date'],
                'amount'            => $data['amount'],
                'deposit_date'      => $data['deposit_date'] ?? null,
                'deposit_reference' => $data['deposit_reference'] ?? null,
                'bank_name'         => $data['bank_name'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'status'            => RecoveryStatus::DRAFT,
                'created_by'        => $data['created_by'],
            ]);
        });
    }

    public function updateRecoveryDraft(CpfAdvanceRecovery $recovery, array $data): CpfAdvanceRecovery
    {
        if (! $recovery->isEditable()) {
            throw new \RuntimeException('Only draft recoveries can be edited.');
        }

        $recovery->update([
            'recovery_date'     => $data['recovery_date'] ?? $recovery->recovery_date,
            'amount'            => $data['amount'] ?? $recovery->amount,
            'deposit_date'      => $data['deposit_date'] ?? $recovery->deposit_date,
            'deposit_reference' => $data['deposit_reference'] ?? $recovery->deposit_reference,
            'bank_name'         => $data['bank_name'] ?? $recovery->bank_name,
            'remarks'           => $data['remarks'] ?? $recovery->remarks,
        ]);

        return $recovery->fresh();
    }

    public function submitRecovery(CpfAdvanceRecovery $recovery, int $submittedBy): CpfAdvanceRecovery
    {
        if (! $recovery->canSubmit()) {
            throw new \RuntimeException('Only draft recoveries can be submitted.');
        }

        $recovery->update([
            'status'        => RecoveryStatus::SUBMITTED,
            'submitted_by'  => $submittedBy,
            'submitted_at'  => now(),
            'reject_reason' => null,
        ]);

        return $recovery->fresh();
    }

    /**
     * Admin approves a SUBMITTED recovery. The amount is allocated PRINCIPAL FIRST,
     * with any excess reducing the interest due. A single ADVANCE_RECOVERY credit is
     * posted for the full amount (one deposit = one ledger line); the principal /
     * interest split is recorded on the recovery row for reporting. The remaining
     * per-installment is recalculated across the remaining installments, and the
     * loan completes once both balances reach zero.
     */
    public function approveRecovery(CpfAdvanceRecovery $recovery, int $approvedBy): CpfAdvanceRecovery
    {
        if (! $recovery->canApprove()) {
            throw new \RuntimeException('Only submitted recoveries can be approved.');
        }

        return DB::transaction(function () use ($recovery, $approvedBy) {
            $advance = $recovery->advance()->lockForUpdate()->first();

            if ($advance->status !== AdvanceStatus::APPROVED) {
                throw new \RuntimeException('The linked advance is not in an approved, recoverable state.');
            }

            // Never recover more than the combined (principal + interest) outstanding.
            $amount = min((int) $recovery->amount, (int) $advance->outstanding_amount);

            // Allocate principal first, then interest.
            $principalPart = min($amount, (int) $advance->principal_outstanding);
            $interestPart  = $amount - $principalPart;

            $recovery->update([
                'status'            => RecoveryStatus::APPROVED,
                'amount'            => $amount,
                'principal_applied' => $principalPart,
                'interest_applied'  => $interestPart,
                'approved_by'       => $approvedBy,
                'approved_at'       => now(),
            ]);

            // One deposit -> one ledger credit for the full installment amount.
            $this->ledgerService->create([
                'employee_id'      => $advance->employee_id,
                'transaction_date' => $recovery->recovery_date,
                'transaction_type' => LedgerTransactionType::ADVANCE_RECOVERY,
                'source_type'      => SourceType::ADVANCE_RECOVERY->value,
                'source_id'        => $recovery->id,
                'reference_no'     => $recovery->recovery_no,
                'remarks'          => $interestPart > 0
                    ? "CPF Advance recovery ({$advance->advance_no}) — principal "
                        . number_format($principalPart) . ", interest " . number_format($interestPart)
                    : "CPF Advance recovery ({$advance->advance_no})",
                'debit'            => 0,
                'credit'           => $amount,
                'created_by'       => $approvedBy,
            ]);

            // Update balances.
            $advance->principal_outstanding = max(0, (int) $advance->principal_outstanding - $principalPart);
            $advance->interest_outstanding  = max(0, (int) $advance->interest_outstanding - $interestPart);
            $advance->outstanding_amount    = $advance->principal_outstanding + $advance->interest_outstanding;
            $advance->save();

            if ($advance->outstanding_amount > 0) {
                // Recalculate remaining per-installment over the remaining installments.
                $remaining = max(1, $advance->installment_count - $advance->installmentsPaid());
                $advance->update([
                    'installment_amount' => (int) ceil($advance->outstanding_amount / $remaining),
                ]);
            } else {
                $this->completeAdvance($advance, $approvedBy);
            }

            return $recovery->fresh();
        });
    }

    public function rejectRecovery(CpfAdvanceRecovery $recovery, ?string $reason, int $rejectedBy): CpfAdvanceRecovery
    {
        if (! $recovery->canReject()) {
            throw new \RuntimeException('Only submitted recoveries can be rejected.');
        }

        $recovery->update([
            'status'        => RecoveryStatus::REJECTED,
            'reject_reason' => $reason,
            'rejected_by'   => $rejectedBy,
            'rejected_at'   => now(),
        ]);

        return $recovery->fresh();
    }

    public function deleteRecoveryDraft(CpfAdvanceRecovery $recovery, AttachmentService $attachments): void
    {
        if (! $recovery->canDelete()) {
            throw new \RuntimeException('Only draft recoveries can be deleted.');
        }

        DB::transaction(function () use ($recovery, $attachments) {
            $attachments->purge($recovery);
            $recovery->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * Fully-repaid loan: both principal and interest have been recovered through
     * the installment schedule, so simply mark the loan COMPLETED. No extra ledger
     * posting is made here — the interest was already credited as it was collected
     * within each recovery.
     */
    protected function completeAdvance(CpfAdvance $advance, int $createdBy): void
    {
        $advance->update([
            'status'                => AdvanceStatus::COMPLETED,
            'principal_outstanding' => 0,
            'interest_outstanding'  => 0,
            'outstanding_amount'    => 0,
            'interest_credited'     => true,
            'interest_credited_at'  => now(),
        ]);
    }

    /**
     * Sequential advance number: ADV-YYYY-00001
     */
    protected function generateAdvanceNo(): string
    {
        $year = now()->format('Y');
        $seq  = CpfAdvance::withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('ADV-%s-%05d', $year, $seq);
    }

    /**
     * Sequential recovery number: REC-YYYY-00001
     */
    protected function generateRecoveryNo(): string
    {
        $year = now()->format('Y');
        $seq  = CpfAdvanceRecovery::withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('REC-%s-%05d', $year, $seq);
    }
}
