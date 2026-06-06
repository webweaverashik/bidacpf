<?php
namespace App\Models\Interest;

use App\Models\BaseModel;
use App\Models\Employee\Employee;

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

    public function batch()
    {
        return $this->belongsTo(BankInterestBatch::class, 'bank_interest_batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getSnapshotAttribute(): array
    {
        return $this->calculation_snapshot ?? [];
    }
}
