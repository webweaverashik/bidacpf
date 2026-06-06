<?php
namespace App\Models\Cpf;

use App\Models\Attachment;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfAdvanceRecovery extends BaseModel
{
    use HasCreatedBy, LogsActivity;

    protected $fillable = [
        'cpf_advance_id',
        'recovery_date',
        'amount',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recovery_date' => 'date',
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
            ->logOnly(['cpf_advance_id', 'recovery_date', 'amount', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cpf_advance_recovery');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function advance()
    {
        return $this->belongsTo(CpfAdvance::class, 'cpf_advance_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('recovery_date', now()->month)
            ->whereYear('recovery_date', now()->year);
    }
}
