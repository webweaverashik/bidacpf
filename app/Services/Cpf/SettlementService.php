<?php
namespace App\Services\Cpf;

use App\Enums\AdvanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\RecoveryStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Enums\SourceType;
use App\Models\Cpf\CpfAdvance;
use App\Models\Cpf\CpfAdvanceRecovery;
use App\Models\Cpf\CpfFinalSettlement;
use App\Models\Employee\Employee;
use Illuminate\Support\Facades\DB;

/**
 * CPF Final Settlement service.
 *
 * Responsibilities (mirrors AdvanceService):
 *   - Preview the settlement figures for an employee (closing balance, payable)
 *   - Draft lifecycle: create / update / submit / delete
 *   - Admin approval: post the FINAL_SETTLEMENT closing entry, write off any
 *     open advance, finalise the record, and transition the member out of service
 *   - Admin rejection (no financial posting)
 *
 * Payable policy (confirmed):
 *   total_payable = closing_balance − advance_adjustment
 *   where closing_balance is the running ledger balance as of the settlement
 *   date (ALREADY net of any disbursed advance principal), and advance_adjustment
 *   is the advance interest actually charged on settlement — 0 under the current
 *   waiver. The disbursement debit already removed the principal from the balance,
 *   so it is not deducted again. The full outstanding advance is recorded
 *   separately on the settlement row for the loan-clearance certificate.
 */
class SettlementService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /*
    |--------------------------------------------------------------------------
    | Preview / eligibility
    |--------------------------------------------------------------------------
    */

    /**
     * Compute the settlement figures for an employee as of a settlement date.
     *
     * @return array{closing_balance:int, outstanding_advance:int, advance_adjustment:int, total_payable:int}
     */
    public function preview(Employee $employee, $settlementDate): array
    {
        $closingBalance     = $this->ledgerService->balanceAsOf($employee->id, $settlementDate);
        $outstandingAdvance = $this->outstandingAdvance($employee);

        // Principal already netted by the disbursement debit; 5% interest waived.
        $advanceAdjustment = 0;

        $totalPayable = max(0, $closingBalance - $advanceAdjustment);

        return [
            'closing_balance'     => $closingBalance,
            'outstanding_advance' => $outstandingAdvance,
            'advance_adjustment'  => $advanceAdjustment,
            'total_payable'       => $totalPayable,
        ];
    }

    /**
     * Combined principal + interest still outstanding across the member's open
     * (approved, not-yet-cleared) advances.
     */
    public function outstandingAdvance(Employee $employee): int
    {
        return (int) $employee->advances()
            ->where('status', AdvanceStatus::APPROVED)
            ->where('outstanding_amount', '>', 0)
            ->sum('outstanding_amount');
    }

    /**
     * Whether the employee has advance work mid-approval that should be resolved
     * before settling (a submitted advance, or a submitted recovery). An approved
     * advance with an outstanding balance is fine — it is written off on approval.
     */
    public function hasPendingAdvanceWork(Employee $employee): bool
    {
        $pendingAdvance = $employee->advances()
            ->where('status', AdvanceStatus::SUBMITTED)
            ->exists();

        $pendingRecovery = CpfAdvanceRecovery::query()
            ->whereHas('advance', fn ($q) => $q->where('employee_id', $employee->id))
            ->where('status', RecoveryStatus::SUBMITTED)
            ->exists();

        return $pendingAdvance || $pendingRecovery;
    }

    /**
     * Whether the employee already has a settlement in progress or completed.
     */
    public function hasOpenOrApprovedSettlement(Employee $employee): bool
    {
        return CpfFinalSettlement::forEmployee($employee->id)
            ->whereIn('status', [SettlementStatus::SUBMITTED, SettlementStatus::APPROVED])
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Settlement lifecycle
    |--------------------------------------------------------------------------
    */

    /**
     * Create a DRAFT settlement, snapshotting the figures as of the settlement date.
     */
    public function createDraft(array $data): CpfFinalSettlement
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::findOrFail($data['employee_id']);

            if ($employee->status !== EmployeeStatus::ACTIVE) {
                throw new \RuntimeException('Only active employees can be put up for final settlement.');
            }

            if ($this->hasOpenOrApprovedSettlement($employee)) {
                throw new \RuntimeException('This employee already has a settlement in progress or approved.');
            }

            $type = $this->resolveType($data['settlement_type']);
            $figures = $this->preview($employee, $data['settlement_date']);

            return CpfFinalSettlement::create([
                'settlement_no'       => $this->generateSettlementNo(),
                'employee_id'         => $employee->id,
                'settlement_type'     => $type,
                'application_date'    => $data['application_date'],
                'settlement_date'     => $data['settlement_date'],
                'closing_balance'     => $figures['closing_balance'],
                'outstanding_advance' => $figures['outstanding_advance'],
                'advance_adjustment'  => $figures['advance_adjustment'],
                'total_payable'       => $figures['total_payable'],
                'payee_name'          => $data['payee_name'] ?? ($type->requiresNominee() ? null : $employee->name),
                'payee_relation'      => $data['payee_relation'] ?? ($type->requiresNominee() ? null : 'Self'),
                'payee_detail'        => $data['payee_detail'] ?? null,
                'remarks'             => $data['remarks'] ?? null,
                'status'              => SettlementStatus::DRAFT,
                'created_by'          => $data['created_by'],
            ]);
        });
    }

    /**
     * Update editable fields on a DRAFT settlement, re-snapshotting the figures
     * (the settlement date — hence the balance cut-off — may have changed).
     */
    public function updateDraft(CpfFinalSettlement $settlement, array $data): CpfFinalSettlement
    {
        if (! $settlement->isEditable()) {
            throw new \RuntimeException('Only draft settlements can be edited.');
        }

        $type           = isset($data['settlement_type']) ? $this->resolveType($data['settlement_type']) : $settlement->settlement_type;
        $settlementDate = $data['settlement_date'] ?? $settlement->settlement_date;
        $figures        = $this->preview($settlement->employee, $settlementDate);

        $settlement->update([
            'settlement_type'     => $type,
            'application_date'    => $data['application_date'] ?? $settlement->application_date,
            'settlement_date'     => $settlementDate,
            'closing_balance'     => $figures['closing_balance'],
            'outstanding_advance' => $figures['outstanding_advance'],
            'advance_adjustment'  => $figures['advance_adjustment'],
            'total_payable'       => $figures['total_payable'],
            'payee_name'          => $data['payee_name'] ?? $settlement->payee_name,
            'payee_relation'      => $data['payee_relation'] ?? $settlement->payee_relation,
            'payee_detail'        => $data['payee_detail'] ?? $settlement->payee_detail,
            'remarks'             => $data['remarks'] ?? $settlement->remarks,
        ]);

        return $settlement->fresh();
    }

    /**
     * Officer forwards a DRAFT to the admin for review. Requires a supporting
     * document (retirement order / resignation letter / death certificate).
     */
    public function submit(CpfFinalSettlement $settlement, int $submittedBy): CpfFinalSettlement
    {
        if (! $settlement->canSubmit()) {
            throw new \RuntimeException('Only draft settlements can be submitted.');
        }

        if ($settlement->attachments()->count() === 0) {
            throw new \RuntimeException('A supporting document must be attached before submitting.');
        }

        $settlement->update([
            'status'        => SettlementStatus::SUBMITTED,
            'submitted_by'  => $submittedBy,
            'submitted_at'  => now(),
            'reject_reason' => null,
        ]);

        return $settlement->fresh();
    }

    /**
     * Admin approves a SUBMITTED settlement.
     *
     * Re-snapshots the figures as of the settlement date, posts the
     * FINAL_SETTLEMENT debit that zeroes the CPF account, writes off any open
     * advance, finalises the record, and transitions the member out of service.
     */
    public function approve(CpfFinalSettlement $settlement, int $approvedBy): CpfFinalSettlement
    {
        if (! $settlement->canApprove()) {
            throw new \RuntimeException('Only submitted settlements can be approved.');
        }

        return DB::transaction(function () use ($settlement, $approvedBy) {
            $employee = $settlement->employee()->lockForUpdate()->first();

            if ($employee->status !== EmployeeStatus::ACTIVE) {
                throw new \RuntimeException('This employee has already been settled or is not active.');
            }

            // Re-snapshot — the balance may have moved since the draft was prepared.
            $figures = $this->preview($employee, $settlement->settlement_date);

            // 1) Closing debit that zeroes the CPF account.
            if ($figures['closing_balance'] > 0) {
                $this->ledgerService->create([
                    'employee_id'      => $employee->id,
                    'transaction_date' => $settlement->settlement_date,
                    'transaction_type' => LedgerTransactionType::FINAL_SETTLEMENT,
                    'source_type'      => SourceType::FINAL_SETTLEMENT->value,
                    'source_id'        => $settlement->id,
                    'reference_no'     => $settlement->settlement_no,
                    'remarks'          => "Final settlement payout ({$settlement->settlement_no}) — {$settlement->settlement_type->label()}",
                    'debit'            => $figures['closing_balance'],
                    'credit'           => 0,
                    'created_by'       => $approvedBy,
                ]);
            }

            // 2) Write off any open advance (principal already drawn; interest waived).
            $this->clearOutstandingAdvances($employee, $settlement);

            // 3) Finalise the settlement with the re-snapshotted figures.
            $settlement->update([
                'status'              => SettlementStatus::APPROVED,
                'closing_balance'     => $figures['closing_balance'],
                'outstanding_advance' => $figures['outstanding_advance'],
                'advance_adjustment'  => $figures['advance_adjustment'],
                'total_payable'       => $figures['total_payable'],
                'approval_date'       => now(),
                'approved_by'         => $approvedBy,
            ]);

            // 4) Transition the member out of active service.
            $employee->update([
                'status'          => $settlement->resultingStatus(),
                'is_active'       => false,
                'retirement_date' => $settlement->settlement_type === SettlementType::RETIREMENT
                    ? $settlement->settlement_date
                    : $employee->retirement_date,
            ]);

            return $settlement->fresh();
        });
    }

    /**
     * Admin rejects a SUBMITTED settlement. No financial posting.
     */
    public function reject(CpfFinalSettlement $settlement, ?string $reason, int $rejectedBy): CpfFinalSettlement
    {
        if (! $settlement->canReject()) {
            throw new \RuntimeException('Only submitted settlements can be rejected.');
        }

        $settlement->update([
            'status'        => SettlementStatus::REJECTED,
            'reject_reason' => $reason,
            'rejected_by'   => $rejectedBy,
            'rejected_at'   => now(),
        ]);

        return $settlement->fresh();
    }

    /**
     * Delete a DRAFT settlement (and its attachments).
     */
    public function deleteDraft(CpfFinalSettlement $settlement, AttachmentService $attachments): void
    {
        if (! $settlement->canDelete()) {
            throw new \RuntimeException('Only draft settlements can be deleted.');
        }

        DB::transaction(function () use ($settlement, $attachments) {
            $attachments->purge($settlement);
            $settlement->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * Write off the member's open advances at settlement. No ledger entry is
     * posted — the principal was already disbursed (debited) and never returns,
     * and the interest is waived. Outstanding balances are zeroed so the advance
     * drops out of the outstanding-advances reports, with a trace left in remarks.
     */
    protected function clearOutstandingAdvances(Employee $employee, CpfFinalSettlement $settlement): void
    {
        $employee->advances()
            ->where('status', AdvanceStatus::APPROVED)
            ->where('outstanding_amount', '>', 0)
            ->get()
            ->each(function (CpfAdvance $advance) use ($settlement) {
                $advance->update([
                    'principal_outstanding' => 0,
                    'interest_outstanding'  => 0,
                    'outstanding_amount'    => 0,
                    'remarks'               => trim(($advance->remarks ? $advance->remarks . ' ' : '')
                        . "[Written off at final settlement {$settlement->settlement_no}]"),
                ]);
            });
    }

    /**
     * Normalise a settlement type given as an enum or a string value.
     */
    protected function resolveType(SettlementType | string $type): SettlementType
    {
        return $type instanceof SettlementType ? $type : SettlementType::from($type);
    }

    /**
     * Sequential settlement number: STL-YYYY-00001
     */
    protected function generateSettlementNo(): string
    {
        $year = now()->format('Y');
        $seq  = CpfFinalSettlement::withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('STL-%s-%05d', $year, $seq);
    }
}
