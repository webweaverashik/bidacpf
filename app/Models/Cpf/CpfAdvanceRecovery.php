<?php
namespace App\Models\Cpf;

use App\Enums\RecoveryStatus;
use App\Models\Attachment;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class CpfAdvanceRecovery extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsModelActivity;

    // Activity-log config
    protected ?string $auditLogName  = 'cpf_advance_loan';
    protected ?string $auditLabel    = 'CPF Advance Recovery';
    protected array $auditAttributes = [
        'recovery_no', 'cpf_advance_id', 'recovery_date', 'amount',
        'principal_applied', 'interest_applied',
        'deposit_date', 'deposit_reference', 'bank_name', 'status',
        'approved_by', 'submitted_by', 'rejected_by', 'reject_reason', 'remarks',
    ];

    protected $fillable = [
        'recovery_no', 'cpf_advance_id', 'recovery_date', 'amount',
        'principal_applied', 'interest_applied',
        'deposit_date', 'deposit_reference', 'bank_name', 'status',
        'submitted_at', 'approved_at', 'rejected_at', 'remarks', 'reject_reason',
        'created_by', 'submitted_by', 'approved_by', 'rejected_by',
    ];

    protected function casts(): array
    {
        return [
            'recovery_date' => 'date',
            'deposit_date'  => 'date',
            'submitted_at'  => 'datetime',
            'approved_at'   => 'datetime',
            'rejected_at'   => 'datetime',
            'status'        => RecoveryStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function advance()
    {
        return $this->belongsTo(CpfAdvance::class, 'cpf_advance_id');
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
    public function scopeApproved($query)
    {
        return $query->where('status', RecoveryStatus::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', RecoveryStatus::SUBMITTED);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('recovery_date', now()->month)
            ->whereYear('recovery_date', now()->year);
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers
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

    public function firstAttachment()
    {
        return $this->attachments()->latest('id')->first();
    }
}
