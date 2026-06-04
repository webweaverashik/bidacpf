<?php
namespace App\Models;

use App\Enums\BatchStatus;

class BankInterestBatch extends BaseModel
{
    protected $fillable = ['distribution_date', 'fiscal_year', 'total_interest_amount', 'total_eligible_balance', 'status', 'remarks', 'created_by', 'posted_by', 'posted_at'];

    protected function casts(): array
    {
        return [
            'distribution_date'      => 'date',
            'posted_at'              => 'datetime',
            'status'                 => BatchStatus::class,
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
     * Creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Posted batches.
     */
    public function scopePosted($query)
    {
        return $query->where('status', BatchStatus::POSTED);
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
