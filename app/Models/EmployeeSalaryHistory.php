<?php
namespace App\Models;

class EmployeeSalaryHistory extends BaseModel
{
    protected $fillable = ['employee_id', 'pay_scale_step_id', 'effective_date', 'change_type', 'remarks', 'created_by'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
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
     * Pay scale step.
     */
    public function payScaleStep()
    {
        return $this->belongsTo(PayScaleStep::class);
    }

    /**
     * Creator.
     */
    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
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
