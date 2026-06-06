<?php
namespace App\Http\Requests\EmployeeSalary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee_salary.create');
    }

    public function rules(): array
    {
        return [
            'pay_scale_step_id' => ['required', 'integer', 'exists:pay_scale_steps,id'],
            'change_type'       => ['required', 'in:initial,annual_increment,promotion,revision'],
            'remarks'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pay_scale_step_id' => 'pay scale step',
            'change_type'       => 'change type',
        ];
    }
}
