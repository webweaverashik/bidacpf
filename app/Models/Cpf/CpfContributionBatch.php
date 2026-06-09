<?php
namespace App\Models\Cpf;

use App\Enums\BatchStatus;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfContributionBatch extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsModelActivity;

        // Activity-log config (replaces getActivitylogOptions)
    protected ?string $auditLogName  = 'cpf_contribution_batch';
    protected ?string $auditLabel    = 'CPF Contribution';
    protected array $auditAttributes = ['contribution_month', 'fiscal_year', 'status', 'submitted_at', 'submitted_by', 'remarks'];

    protected $fillable = [
        'contribution_month',
        'fiscal_year',
        'submitted_at',
        'submitted_by',
        'status',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contribution_month' => 'date',
            'submitted_at'       => 'datetime',
            'status'             => BatchStatus::class,
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

    public function scopeReversed($query)
    {
        return $query->where('status', BatchStatus::REVERSED);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
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

    public function canBeSubmitted(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }

    public function canBeReversed(): bool
    {
        return $this->status === BatchStatus::SUBMITTED;
    }

    public function isEditable(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }
}
