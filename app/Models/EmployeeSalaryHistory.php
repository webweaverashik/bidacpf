<?php
namespace App\Models;

use App\Traits\HasCreatedBy;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeSalaryHistory extends BaseModel
{
    use HasCreatedBy, LogsActivity;

    protected $fillable = ['employee_id', 'pay_scale_step_id', 'effective_date', 'change_type', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    /*
     Activity Log Options
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'pay_scale_step_id', 'effective_date', 'change_type', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employee_salary_history');
    }

    /**
     * Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Pay scale step.
     */
    public function payScaleStep()
    {
        return $this->belongsTo(PayScaleStep::class);
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
}
