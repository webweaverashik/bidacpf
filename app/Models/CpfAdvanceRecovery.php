<?php
namespace App\Models;

use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
    use HasCreatedBy, LogsActivity;

    protected $fillable = ['cpf_advance_id', 'recovery_date', 'amount', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'recovery_date' => 'date',
        ];
    }

    /*
     Activity Log Options
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cpf_advance_id', 'recovery_date', 'amount', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cpf_advance_recovery');
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
