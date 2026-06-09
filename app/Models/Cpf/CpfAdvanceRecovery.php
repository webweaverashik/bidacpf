<?php
namespace App\Models\Cpf;

use App\Models\Attachment;
use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;

class CpfAdvanceRecovery extends BaseModel
{
    use HasCreatedBy, LogsModelActivity;

    // Activity-log config (replaces getActivitylogOptions)
    protected ?string $auditLogName  = 'cpf_advance_loan';
    protected ?string $auditLabel    = 'CPF Advance Recovery';
    protected array $auditAttributes = ['cpf_advance_id', 'recovery_date', 'amount', 'remarks'];

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
