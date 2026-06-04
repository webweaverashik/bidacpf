<?php
namespace App\Models;

class PayScaleStep extends BaseModel
{
    protected $fillable = ['pay_scale_id', 'grade', 'step', 'basic_salary'];

    /**
     * Parent pay scale.
     */
    public function payScale()
    {
        return $this->belongsTo(PayScale::class);
    }

    /**
     * Employees currently assigned.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Salary history records.
     */
    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    /**
     * Grade filter.
     */
    public function scopeGrade($query, int $grade)
    {
        return $query->where('grade', $grade);
    }

    /**
     * Salary display.
     */
    public function getDisplayNameAttribute(): string
    {
        return sprintf('Grade %s - Step %s', $this->grade, $this->step);
    }

    /**
     * Salary formatted.
     */
    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->basic_salary);
    }

    /**
     * Quick lookup.
     */
    public static function salary(int $payScaleId, int $grade, int $step): ?int
    {
        return static::query()->where('pay_scale_id', $payScaleId)->where('grade', $grade)->where('step', $step)->value('basic_salary');
    }
}