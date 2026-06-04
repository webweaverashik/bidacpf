<?php
namespace App\Models;

use App\Enums\LedgerTransactionType;

class CpfLedger extends BaseModel
{
    protected $fillable = ['employee_id', 'transaction_date', 'transaction_type', 'source_type', 'source_id', 'reference_no', 'remarks', 'debit', 'credit', 'balance', 'created_by'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'transaction_type' => LedgerTransactionType::class,
        ];
    }

    /**
     * Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Creator.
     */
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Credits only.
     */
    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * Debits only.
     */
    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Filter by employee.
     */
    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Filter by transaction type.
     */
    public function scopeByType($query, LedgerTransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Ledger amount.
     */
    public function amount(): int
    {
        return max($this->debit, $this->credit);
    }

    /**
     * Is debit entry?
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Is credit entry?
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    /**
     * Running balance.
     */
    public function runningBalance(): int
    {
        return $this->balance;
    }

    /**
     * Source label.
     */
    public function getSourceLabelAttribute(): string
    {
        return str($this->source_type)->replace('_', ' ')->title();
    }
}
