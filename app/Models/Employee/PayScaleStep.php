<?php
namespace App\Models\Employee;

use App\Models\BaseModel;

class PayScaleStep extends BaseModel
{
    protected $fillable = ['pay_scale_id', 'grade', 'step', 'basic_salary'];

    public function payScale()
    {
        return $this->belongsTo(PayScale::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    public function scopeGrade($query, int $grade)
    {
        return $query->where('grade', $grade);
    }

    public function getDisplayNameAttribute(): string
    {
        return sprintf('Grade %s - Step %s', $this->grade, $this->step);
    }

    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->basic_salary);
    }

    public static function salary(int $payScaleId, int $grade, int $step): ?int
    {
        return static::query()
            ->where('pay_scale_id', $payScaleId)
            ->where('grade', $grade)
            ->where('step', $step)
            ->value('basic_salary');
    }
}
