<?php
namespace App\Models\Interest;

use App\Models\BaseModel;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInterestDistribution extends BaseModel
{
    protected $fillable = [
        'bank_interest_batch_id',
        'employee_id',
        'eligible_balance',
        'interest_amount',
        'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'calculation_snapshot' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(BankInterestBatch::class, 'bank_interest_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Snapshot accessors (read the immutable calculation record)
    |--------------------------------------------------------------------------
    */
    public function getSnapshotAttribute(): array
    {
        return $this->calculation_snapshot ?? [];
    }

    /**
     * Distribution ratio used at calculation time (0..1).
     */
    public function getRatioAttribute(): float
    {
        return (float) ($this->snapshot['ratio'] ?? 0);
    }

    /**
     * Raw (pre-rounding) interest figure captured in the snapshot.
     */
    public function getCalculatedInterestAttribute(): float
    {
        return (float) ($this->snapshot['calculated_interest'] ?? $this->interest_amount);
    }
}
