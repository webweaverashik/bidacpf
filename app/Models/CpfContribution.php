<?php
namespace App\Models;

class CpfContribution extends BaseModel
{
    protected $fillable = ['cpf_contribution_batch_id', 'employee_id', 'basic_salary', 'employee_contribution', 'government_contribution', 'remarks'];

    /**
     * Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Contribution batch.
     */
    public function batch()
    {
        return $this->belongsTo(CpfContributionBatch::class, 'cpf_contribution_batch_id');
    }

    /**
     * Total contribution.
     */
    public function totalContribution(): int
    {
        return $this->employee_contribution + $this->government_contribution;
    }
}
