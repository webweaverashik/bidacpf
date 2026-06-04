<?php
namespace App\Models;

use App\Enums\BatchStatus;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfContributionBatch extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsActivity;

    protected $fillable = ['contribution_month', 'fiscal_year', 'status', 'remarks', 'submitted_by', 'submitted_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'contribution_month' => 'date',
            'submitted_at'       => 'date',
            'status'             => BatchStatus::class,
        ];
    }

    /*
     Activity Log Options
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['contribution_month', 'fiscal_year', 'status', 'submitted_at', 'submitted_by', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cpf_contribution_batch');
    }

    /**
     * Contributions under this batch.
     */
    public function contributions()
    {
        return $this->hasMany(CpfContribution::class);
    }

    /**
     * Submitted by.
     */
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Draft batches.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', BatchStatus::DRAFT);
    }

    /**
     * Submitted batches.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', BatchStatus::SUBMITTED);
    }

    /**
     * Reversed batches.
     */
    public function scopeReversed($query)
    {
        return $query->where('status', BatchStatus::REVERSED);
    }

    /**
     * Total employee contribution.
     */
    public function totalEmployeeContribution(): int
    {
        return (int) $this->contributions()->sum('employee_contribution');
    }

    /**
     * Total government contribution.
     */
    public function totalGovernmentContribution(): int
    {
        return (int) $this->contributions()->sum('government_contribution');
    }

    /**
     * Total contribution.
     */
    public function totalContribution(): int
    {
        return $this->totalEmployeeContribution() + $this->totalGovernmentContribution();
    }

    /**
     * Number of employees included.
     */
    public function employeeCount(): int
    {
        return $this->contributions()->count();
    }

    /**
     * Batch month label.
     * Example: July 2026
     */
    public function getMonthLabelAttribute(): string
    {
        return $this->contribution_month->format('F Y');
    }

    /**
     * Whether batch can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }

    /**
     * Whether batch can be reversed.
     */
    public function canBeReversed(): bool
    {
        return $this->status === BatchStatus::SUBMITTED;
    }

    /**
     * Whether batch is editable.
     */
    public function isEditable(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }
}
