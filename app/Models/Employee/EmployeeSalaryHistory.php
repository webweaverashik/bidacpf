<?php
namespace App\Models\Employee;

use App\Models\BaseModel;
use App\Traits\HasCreatedBy;
use App\Traits\LogsModelActivity;

class EmployeeSalaryHistory extends BaseModel
{
    use HasCreatedBy, LogsModelActivity;

    protected ?string $auditLogName  = 'employee_salary_history';
    protected ?string $auditLabel    = 'Salary History';
    protected array $auditAttributes = ['employee_id', 'pay_scale_step_id', 'effective_date', 'change_type', 'remarks'];

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
