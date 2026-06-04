<?php
namespace App\Models;

use App\Traits\HasCreatedBy;

/*
Business Flow
Employee
    │
    ▼
CPF Advance
    │
    ▼
Ledger Debit Entry
    │
    ▼
Recoveries
    │
    ▼
Ledger Credit Entries
    │
    ▼
Outstanding Amount Reduced
*/

class CpfAdvanceRecovery extends BaseModel
{
    use HasCreatedBy;
    
    protected $fillable = ['cpf_advance_id', 'recovery_date', 'amount', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'recovery_date' => 'date',
        ];
    }

    /**
     * Parent advance.
     */
    public function advance()
    {
        return $this->belongsTo(CpfAdvance::class, 'cpf_advance_id');
    }

    /**
     * Supporting documents.
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Recovery this month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('recovery_date', now()->month)->whereYear('recovery_date', now()->year);
    }
}
