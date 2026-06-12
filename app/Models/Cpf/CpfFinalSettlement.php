<?php
namespace App\Models\Cpf;

use App\Enums\EmployeeStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Models\Attachment;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Models\Employee\Employee;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A CPF Final Settlement — the terminal, balance-zeroing operation that closes
 * a member's account on retirement, resignation, or death.
 *
 * Like CpfAdvance it is a *transactional* record: on approval the service posts
 * a FINAL_SETTLEMENT ledger entry, transitions the employee's status, and stamps
 * the retirement date. This model carries only state + helpers; the posting logic
 * lives in App\Services\SettlementService.
 */
class CpfFinalSettlement extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsModelActivity;

    // Activity-log config
    protected ?string $auditLogName  = 'cpf_final_settlement';
    protected ?string $auditLabel    = 'CPF Final Settlement';
    protected array $auditAttributes = [
        'settlement_no', 'employee_id', 'settlement_type', 'application_date', 'settlement_date',
        'closing_balance', 'outstanding_advance', 'advance_adjustment', 'total_payable',
        'payee_name', 'payee_relation', 'status',
        'approval_date', 'approved_by', 'submitted_by', 'rejected_by', 'reject_reason', 'remarks',
    ];

    protected $fillable = [
        'settlement_no', 'employee_id', 'settlement_type', 'application_date', 'settlement_date',
        'closing_balance', 'outstanding_advance', 'advance_adjustment', 'total_payable',
        'payee_name', 'payee_relation', 'payee_detail',
        'status', 'approval_date', 'submitted_at', 'rejected_at', 'remarks', 'reject_reason',
        'created_by', 'submitted_by', 'approved_by', 'rejected_by',
    ];

    protected function casts(): array
    {
        return [
            'settlement_type'  => SettlementType::class,
            'application_date' => 'date',
            'settlement_date'  => 'date',
            'approval_date'    => 'date',
            'submitted_at'     => 'datetime',
            'rejected_at'      => 'datetime',
            'status'           => SettlementStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeDraft($query)
    {
        return $query->where('status', SettlementStatus::DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', SettlementStatus::SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', SettlementStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', SettlementStatus::REJECTED);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers (delegate to the enum)
    |--------------------------------------------------------------------------
    */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function canSubmit(): bool
    {
        return $this->status->canSubmit();
    }

    public function canApprove(): bool
    {
        return $this->status->canApprove();
    }

    public function canReject(): bool
    {
        return $this->status->canReject();
    }

    public function canDelete(): bool
    {
        return $this->status->canDelete();
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /*
    |--------------------------------------------------------------------------
    | Business helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Payee name — defaults to the member; a nominee is named for deceased
     * settlements (payee_name populated at draft).
     */
    public function payeeName(): string
    {
        return $this->payee_name ?: ($this->employee?->name ?? '—');
    }

    /**
     * The employee status the member transitions to once this settlement is
     * approved (delegates to the settlement type).
     */
    public function resultingStatus(): EmployeeStatus
    {
        return $this->settlement_type->resultingStatus();
    }

    /**
     * Whether this settlement still has an advance to clear at the time of
     * settlement (drives the loan-clearance certificate).
     */
    public function hasOutstandingAdvance(): bool
    {
        return (int) $this->outstanding_advance > 0;
    }

    public function firstAttachment()
    {
        return $this->attachments()->latest('id')->first();
    }
}
