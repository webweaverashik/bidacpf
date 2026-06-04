<?php
namespace App\Models;

use App\Enums\BatchStatus;
use App\Traits\HasCreatedBy;

class BankInterestBatch extends BaseModel
{
    use HasCreatedBy;

    protected $fillable = ['distribution_date', 'fiscal_year', 'total_interest_amount', 'total_eligible_balance', 'status', 'remarks', 'created_by', 'submitted_by', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
            'submitted_at'      => 'datetime',
            'status'            => BatchStatus::class,
        ];
    }

    /**
     * Interest distributions.
     */
    public function distributions()
    {
        return $this->hasMany(BankInterestDistribution::class);
    }

    /**
     * Posted by.
     */
    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
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

    public function canBeSubmitted(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }

    public function canBeReversed(): bool
    {
        return $this->status === BatchStatus::SUBMITTED;
    }

    /**
     * Total distributed amount.
     */
    public function totalDistributed(): float
    {
        return (float) $this->distributions()->sum('interest_amount');
    }

    /**
     * Distribution count.
     */
    public function distributionCount(): int
    {
        return $this->distributions()->count();
    }
}
