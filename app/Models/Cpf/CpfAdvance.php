<?php
namespace App\Models\Cpf;

use App\Enums\AdvanceStatus;
use App\Models\Attachment;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Models\Employee\Employee;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class CpfAdvance extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsModelActivity;

    // Activity-log config
    protected ?string $auditLogName  = 'cpf_advance_loan';
    protected ?string $auditLabel    = 'Employee CPF Advance';
    protected array $auditAttributes = [
        'advance_no', 'employee_id', 'application_date', 'requested_amount',
        'approved_amount', 'interest_rate', 'interest_amount', 'installment_count',
        'installment_amount', 'outstanding_amount', 'interest_credited', 'status',
        'approval_date', 'approved_by', 'submitted_by', 'rejected_by', 'reject_reason', 'remarks',
    ];

    protected $fillable = [
        'advance_no', 'employee_id', 'application_date', 'requested_amount',
        'approved_amount', 'interest_rate', 'interest_amount', 'installment_count',
        'installment_amount', 'principal_outstanding', 'interest_outstanding', 'outstanding_amount',
        'interest_credited', 'interest_credited_at',
        'status', 'approval_date', 'submitted_at', 'rejected_at', 'remarks', 'reject_reason',
        'created_by', 'submitted_by', 'approved_by', 'rejected_by',
    ];

    protected function casts(): array
    {
        return [
            'application_date'     => 'date',
            'approval_date'        => 'date',
            'submitted_at'         => 'datetime',
            'rejected_at'          => 'datetime',
            'interest_credited'    => 'boolean',
            'interest_credited_at' => 'datetime',
            'status'               => AdvanceStatus::class,
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

    public function recoveries()
    {
        return $this->hasMany(CpfAdvanceRecovery::class);
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
        return $query->where('status', AdvanceStatus::DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', AdvanceStatus::SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AdvanceStatus::APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', AdvanceStatus::COMPLETED);
    }

    public function scopeOutstanding($query)
    {
        return $query->where('status', AdvanceStatus::APPROVED)
            ->where('outstanding_amount', '>', 0);
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

    public function canRecover(): bool
    {
        return $this->status->canRecover() && $this->outstanding_amount > 0;
    }

    public function canDelete(): bool
    {
        return $this->status->canDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Business helpers
    |--------------------------------------------------------------------------
    */

    /**
     * The principal the schedule is based on: the admin-approved amount once
     * approved, otherwise the officer's requested amount (for live previews).
     */
    public function effectiveAmount(): int
    {
        return (int) ($this->approved_amount ?? $this->requested_amount);
    }

    /**
     * Interest = effectiveAmount * rate%. Repaid as part of the installment
     * schedule (principal-first), NOT gifted. Uses the stored value once approved.
     */
    public function projectedInterest(): int
    {
        if ($this->interest_amount) {
            return (int) $this->interest_amount;
        }

        return (int) round($this->effectiveAmount() * ($this->interest_rate / 100));
    }

    /** Total the employee repays over the schedule = principal + interest. */
    public function totalPayable(): int
    {
        return $this->effectiveAmount() + $this->projectedInterest();
    }

    /** Per-installment of the TOTAL repayable (rounded up). */
    public function projectedInstallment(): int
    {
        if ($this->installment_amount) {
            return (int) $this->installment_amount;
        }

        return $this->installment_count > 0
            ? (int) ceil($this->totalPayable() / $this->installment_count)
            : $this->totalPayable();
    }

    /**
     * Final installment, which absorbs the rounding remainder so the schedule
     * sums exactly to the total repayable.
     */
    public function lastInstallment(): int
    {
        $count = (int) $this->installment_count;
        if ($count <= 1) {
            return $this->totalPayable();
        }

        return (int) ($this->totalPayable() - $this->projectedInstallment() * ($count - 1));
    }

    /** Combined amount still to recover (principal + interest). */
    public function combinedOutstanding(): int
    {
        return (int) $this->outstanding_amount;
    }

    /** Total recovered so far across principal + interest. */
    public function totalRecovered(): int
    {
        if ($this->approved_amount === null) {
            return 0;
        }

        return (int) ($this->totalPayable() - $this->outstanding_amount);
    }

    /** Sum of all APPROVED recovery rows (for cross-checking). */
    public function approvedRecoveriesTotal(): int
    {
        return (int) $this->recoveries()
            ->where('status', \App\Enums\RecoveryStatus::APPROVED)
            ->sum('amount');
    }

    /** Number of installments paid = count of approved recoveries. */
    public function installmentsPaid(): int
    {
        return (int) $this->recoveries()
            ->where('status', \App\Enums\RecoveryStatus::APPROVED)
            ->count();
    }

    /** Repayment progress percentage over the total repayable (0–100). */
    public function progressPercent(): float
    {
        if (! $this->approved_amount || $this->totalPayable() <= 0) {
            return 0;
        }

        return round(($this->totalRecovered() / $this->totalPayable()) * 100, 1);
    }

    public function isFullyRepaid(): bool
    {
        return $this->status === AdvanceStatus::APPROVED && $this->outstanding_amount <= 0;
    }

    public function firstAttachment()
    {
        return $this->attachments()->latest('id')->first();
    }
}
