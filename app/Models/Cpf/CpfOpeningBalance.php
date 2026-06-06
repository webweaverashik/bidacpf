<?php
namespace App\Models\Cpf;

use App\Models\BaseModel;
use App\Models\Employee\Employee;
use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfOpeningBalance extends BaseModel
{
    use HasCreatedBy, LogsActivity;

    protected $fillable = [
        'employee_id',
        'effective_date',
        'self_contribution',
        'government_contribution',
        'interest_amount',
        'outstanding_advance',
        'net_balance',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_id',
                'effective_date',
                'self_contribution',
                'government_contribution',
                'interest_amount',
                'outstanding_advance',
                'net_balance',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cpf_opening_balance');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeEffectiveAfter($query, $date)
    {
        return $query->where('effective_date', '>=', $date);
    }

    public function scopeEffectiveBefore($query, $date)
    {
        return $query->where('effective_date', '<=', $date);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */
    public function principalAmount(): int
    {
        return $this->self_contribution + $this->government_contribution;
    }

    public function grossBalance(): int
    {
        return $this->self_contribution + $this->government_contribution + $this->interest_amount;
    }

    public function calculatedNetBalance(): int
    {
        return $this->grossBalance() - $this->outstanding_advance;
    }

    public function isBalanced(): bool
    {
        return $this->calculatedNetBalance() === $this->net_balance;
    }

    public function getSummaryAttribute(): string
    {
        return sprintf(
            'Self: %s | Govt: %s | Interest: %s | Advance: %s | Net: %s',
            number_format($this->self_contribution),
            number_format($this->government_contribution),
            number_format($this->interest_amount),
            number_format($this->outstanding_advance),
            number_format($this->net_balance),
        );
    }
}
