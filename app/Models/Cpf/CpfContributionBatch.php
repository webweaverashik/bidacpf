<?php
namespace App\Models\Cpf;

use App\Enums\BatchStatus;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CpfContributionBatch extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsModelActivity;

    protected ?string $auditLogName  = 'cpf_contribution_batch';
    protected ?string $auditLabel    = 'CPF Contribution';
    protected array $auditAttributes = ['contribution_month', 'fiscal_year', 'status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'reversed_at', 'reversed_by', 'remarks', 'employee_rate', 'government_rate'];

    protected $fillable = ['contribution_month', 'fiscal_year', 'status', 'remarks', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'reversed_by', 'reversed_at', 'created_by', 'employee_rate', 'government_rate'];

    protected function casts(): array
    {
        return [
            'contribution_month' => 'date',
            'submitted_at'       => 'datetime',
            'approved_at'        => 'datetime',
            'reversed_at'        => 'datetime',
            'status'             => BatchStatus::class,
            'employee_rate'      => 'decimal:2',
            'government_rate'    => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function contributions()
    {
        return $this->hasMany(CpfContribution::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeDraft($query)
    {
        return $query->where('status', BatchStatus::DRAFT);
    }
    public function scopeSubmitted($query)
    {
        return $query->where('status', BatchStatus::SUBMITTED);
    }
    public function scopeApproved($query)
    {
        return $query->where('status', BatchStatus::APPROVED);
    }
    public function scopeReversed($query)
    {
        return $query->where('status', BatchStatus::REVERSED);
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregates
    |--------------------------------------------------------------------------
    */
    public function totalEmployeeContribution(): int
    {
        return (int) $this->contributions()->sum('employee_contribution');
    }

    public function totalGovernmentContribution(): int
    {
        return (int) $this->contributions()->sum('government_contribution');
    }

    public function totalContribution(): int
    {
        return $this->totalEmployeeContribution() + $this->totalGovernmentContribution();
    }

    public function employeeCount(): int
    {
        return $this->contributions()->count();
    }

    public function getMonthLabelAttribute(): string
    {
        return $this->contribution_month->format('F Y');
    }

    /*
    |--------------------------------------------------------------------------
    | State helpers (delegate to the enum)
    |--------------------------------------------------------------------------
    */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
    public function canBeSubmitted(): bool
    {
        return $this->status->canSubmit();
    }
    public function canBeApproved(): bool
    {
        return $this->status->canApprove();
    }
    public function canBeRejected(): bool
    {
        return $this->status->canReject();
    }
    public function canBeReversed(): bool
    {
        return $this->status->canReverse();
    }
}
