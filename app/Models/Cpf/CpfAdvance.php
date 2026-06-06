<?php
namespace App\Models\Cpf;

use App\Enums\AdvanceStatus;
use App\Models\Attachment;
use App\Models\Auth\User;
use App\Models\BaseModel;
use App\Models\Employee\Employee;
use App\Traits\HasCreatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CpfAdvance extends BaseModel
{
    use SoftDeletes, HasCreatedBy, LogsActivity;

    protected $fillable = [
        'advance_no',
        'employee_id',
        'application_date',
        'approval_date',
        'approved_amount',
        'interest_rate',
        'installment_count',
        'outstanding_amount',
        'status',
        'remarks',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'approval_date'    => 'date',
            'status'           => AdvanceStatus::class,
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
                'advance_no',
                'employee_id',
                'application_date',
                'approval_date',
                'approved_amount',
                'interest_rate',
                'installment_count',
                'outstanding_amount',
                'status',
                'approved_by',
                'remarks',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cpf_advance');
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

    public function recoveries()
    {
        return $this->hasMany(CpfAdvanceRecovery::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopePending($query)
    {
        return $query->where('status', AdvanceStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AdvanceStatus::APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', AdvanceStatus::COMPLETED);
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */
    public function totalRecovered(): int
    {
        return (int) $this->recoveries()->sum('amount');
    }

    public function remainingAmount(): int
    {
        return (int) $this->outstanding_amount;
    }

    public function isCompleted(): bool
    {
        return $this->outstanding_amount <= 0;
    }

    public function firstAttachment()
    {
        return $this->attachments()->first();
    }
}
