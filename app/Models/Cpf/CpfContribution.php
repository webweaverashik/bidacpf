<?php
namespace App\Models\Cpf;

use App\Models\BaseModel;
use App\Models\Employee\Employee;

class CpfContribution extends BaseModel
{
    protected $fillable = [
        'cpf_contribution_batch_id',
        'employee_id',
        'basic_salary',
        'employee_contribution',
        'government_contribution',
        'remarks',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function batch()
    {
        return $this->belongsTo(CpfContributionBatch::class, 'cpf_contribution_batch_id');
    }

    public function totalContribution(): int
    {
        return $this->employee_contribution + $this->government_contribution;
    }
}
