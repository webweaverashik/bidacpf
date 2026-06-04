<?php
namespace App\Models;

use App\Enums\BatchStatus;

class CpfContributionBatch extends BaseModel
{
    protected $fillable = ['month', 'year', 'fiscal_year', 'posting_date', 'status', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'status'       => BatchStatus::class,
        ];
    }

    /**
     * Batch creator.
     */
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Contributions under this batch.
     */
    public function contributions()
    {
        return $this->hasMany(CpfContribution::class);
    }

    /**
     * Draft batches.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', BatchStatus::DRAFT);
    }

    /**
     * Posted batches.
     */
    public function scopePosted($query)
    {
        return $query->where('status', BatchStatus::POSTED);
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
     * Display title.
     */
    public function getTitleAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1)) . ' ' . $this->year;
    }
}