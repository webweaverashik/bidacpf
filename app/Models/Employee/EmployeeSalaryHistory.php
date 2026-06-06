<?php
namespace App\Models\Employee;

use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeSalaryHistory extends BaseModel
{
    use HasCreatedBy, LogsActivity;

    protected $fillable = [
        'employee_id',
        'pay_scale_step_id',
        'effective_date',
        'change_type',
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
            ->logOnly(['employee_id', 'pay_scale_step_id', 'effective_date', 'change_type', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employee_salary_history');
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

    public function payScaleStep()
    {
        return $this->belongsTo(PayScaleStep::class);
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
}
