<?php
namespace App\Models;

use App\Traits\HasCreatedBy;

/*
An auditor can later trace:

Ledger Entry
        ↓
Opening Balance Ledger
        ↓
CpfOpeningBalance Record
        ↓
Imported Legacy Data
*/

class CpfOpeningBalance extends BaseModel
{
    use HasCreatedBy;
    
    protected $fillable = ['employee_id', 'effective_date', 'self_contribution', 'government_contribution', 'interest_amount', 'outstanding_advance', 'net_balance', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    /**
     * CPF Member.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Effective after date.
     */
    public function scopeEffectiveAfter($query, $date)
    {
        return $query->where('effective_date', '>=', $date);
    }

    /**
     * Effective before date.
     */
    public function scopeEffectiveBefore($query, $date)
    {
        return $query->where('effective_date', '<=', $date);
    }

    /**
     * Opening principal amount.
     *
     * Self + Government Contribution
     */
    public function principalAmount(): int
    {
        return $this->self_contribution + $this->government_contribution;
    }

    /**
     * Gross balance before advance adjustment.
     */
    public function grossBalance(): int
    {
        return $this->self_contribution + $this->government_contribution + $this->interest_amount;
    }

    /**
     * Verify net balance.
     */
    public function calculatedNetBalance(): int
    {
        return $this->grossBalance() - $this->outstanding_advance;
    }

    /**
     * Check whether imported balance is valid.
     */
    public function isBalanced(): bool
    {
        return $this->calculatedNetBalance() === $this->net_balance;
    }

    /**
     * Formatted migration summary.
     */
    public function getSummaryAttribute(): string
    {
        return sprintf('Self: %s | Govt: %s | Interest: %s | Advance: %s | Net: %s', number_format($this->self_contribution), number_format($this->government_contribution), number_format($this->interest_amount), number_format($this->outstanding_advance), number_format($this->net_balance));
    }
}
