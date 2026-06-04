<?php
namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\PayScaleStep;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryService
{
    /**
     * Change pay scale step.
     */
    public function updateStep(Employee $employee, PayScaleStep $step, string $changeType, int $createdBy, ?string $remarks = null): void
    {
        DB::transaction(function () use ($employee, $step, $changeType, $createdBy, $remarks) {
            $employee->update([
                'pay_scale_step_id' => $step->id,
            ]);

            EmployeeSalaryHistory::create([
                'employee_id'       => $employee->id,
                'pay_scale_step_id' => $step->id,
                'effective_date'    => now(),
                'change_type'       => $changeType,
                'remarks'           => $remarks,
                'created_by'        => $createdBy,
            ]);
        });
    }

    /**
     * Annual increment.
     */
    public function annualIncrement(Employee $employee, int $createdBy): void
    {
        $current = $employee->payScaleStep;

        $nextStep = PayScaleStep::query()
            ->where('pay_scale_id', $current->pay_scale_id)
            ->where('grade', $current->grade)
            ->where('step', $current->step + 1)
            ->first();

        if (! $nextStep) {
            return;
        }

        $this->updateStep($employee, $nextStep, 'annual_increment', $createdBy);
    }
}
