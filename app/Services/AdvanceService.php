<?php
namespace App\Services;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerTransactionType;
use App\Enums\SourceType;
use App\Models\CpfAdvance;
use App\Models\CpfAdvanceRecovery;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class AdvanceService
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Check advance eligibility.
     */
    public function eligibleAmount(Employee $employee): int
    {
        return (int) floor($employee->currentBalance() * (setting('advance_limit_percentage', 80) / 100));
    }

    /**
     * Create advance.
     */
    public function create(array $data): CpfAdvance
    {
        return DB::transaction(function () use ($data) {
            return CpfAdvance::create([ ...$data, 'status' => AdvanceStatus::PENDING]);
        });
    }

    /**
     * Approve advance.
     */
    public function approve(CpfAdvance $advance, int $approvedBy): CpfAdvance
    {
        return DB::transaction(function () use ($advance, $approvedBy) {
            $advance->update([
                'status'             => AdvanceStatus::APPROVED,
                'approval_date'      => now(),
                'approved_by'        => $approvedBy,
                'outstanding_amount' => $advance->approved_amount,
            ]);

            $this->ledgerService->create([
                'employee_id'      => $advance->employee_id,
                'transaction_date' => now(),
                'transaction_type' => LedgerTransactionType::ADVANCE_DISBURSEMENT,
                'source_type'      => SourceType::ADVANCE,
                'source_id'        => $advance->id,
                'reference_no'     => $advance->advance_no,
                'remarks'          => 'CPF Advance Disbursement',
                'debit'            => $advance->approved_amount,
                'credit'           => 0,
                'created_by'       => $approvedBy,
            ]);

            return $advance->fresh();
        });
    }

    /**
     * Record recovery.
     */
    public function recovery(CpfAdvance $advance, int $amount, string | null $remarks, int $createdBy): CpfAdvanceRecovery
    {
        return DB::transaction(function () use ($advance, $amount, $remarks, $createdBy) {
            $recovery = CpfAdvanceRecovery::create([
                'cpf_advance_id' => $advance->id,
                'recovery_date'  => now(),
                'amount'         => $amount,
                'remarks'        => $remarks,
                'created_by'     => $createdBy,
            ]);

            $advance->decrement('outstanding_amount', $amount);

            $this->ledgerService->create([
                'employee_id'      => $advance->employee_id,
                'transaction_date' => now(),
                'transaction_type' => LedgerTransactionType::ADVANCE_RECOVERY,
                'source_type'      => SourceType::ADVANCE_RECOVERY,
                'source_id'        => $recovery->id,
                'reference_no'     => $advance->advance_no,
                'remarks'          => 'CPF Advance Recovery',
                'debit'            => 0,
                'credit'           => $amount,
                'created_by'       => $createdBy,
            ]);

            if ($advance->fresh()->outstanding_amount <= 0) {
                $advance->update([
                    'status'             => AdvanceStatus::COMPLETED,
                    'outstanding_amount' => 0,
                ]);
            }

            return $recovery;
        });
    }
}
