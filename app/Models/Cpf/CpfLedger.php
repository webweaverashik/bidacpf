<?php
namespace App\Models\Cpf;

use App\Enums\LedgerTransactionType;
use App\Models\BaseModel;
use App\Models\Employee\Employee;
use App\Traits\HasCreatedBy;

class CpfLedger extends BaseModel
{
    use HasCreatedBy;

    protected $fillable = [
        'employee_id',
        'transaction_date',
        'transaction_type',
        'source_type',
        'source_id',
        'reference_no',
        'remarks',
        'debit',
        'credit',
        'balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'transaction_type' => LedgerTransactionType::class,
        ];
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
    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByType($query, LedgerTransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    public function amount(): int
    {
        return max($this->debit, $this->credit);
    }

    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    public function runningBalance(): int
    {
        return $this->balance;
    }

    public function getSourceLabelAttribute(): string
    {
        return str($this->source_type)->replace('_', ' ')->title();
    }
}
