<?php
namespace App\Models;

/*
Bank Provides Interest
        │
        ▼
Create Interest Batch
        │
        ▼
Calculate Eligible Balance
        │
        ▼
Generate Distributions
        │
        ▼
Review
        │
        ▼
Post Batch
        │
        ▼
Create Ledger Entries
*/
class BankInterestDistribution extends BaseModel
{
    protected $fillable = ['bank_interest_batch_id', 'employee_id', 'eligible_balance', 'interest_amount', 'calculation_snapshot'];

    protected function casts(): array
    {
        return [
            'calculation_snapshot' => 'array',
        ];
    }

    /**
     * Batch.
     */
    public function batch()
    {
        return $this->belongsTo(BankInterestBatch::class, 'bank_interest_batch_id');
    }

    /**
     * Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Snapshot accessor.
     */
    public function getSnapshotAttribute(): array
    {
        return $this->calculation_snapshot ?? [];
    }
}
