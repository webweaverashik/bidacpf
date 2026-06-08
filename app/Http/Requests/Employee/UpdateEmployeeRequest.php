<?php
namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.update');
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')->id;

        return [
            'cpf_account_no'    => ['required', 'string', 'max:50', "unique:employees,cpf_account_no,{$employeeId}"],
            'name'              => ['required', 'string', 'max:255'],
            'designation'       => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255', "unique:employees,email,{$employeeId}"],
            'mobile_number'     => ['nullable', 'string', 'max:20'],
            'joining_date'      => ['required', 'date'],
            'retirement_date'   => ['nullable', 'date', 'after:joining_date'],
            'pay_scale_step_id' => ['required', 'integer', 'exists:pay_scale_steps,id'],
            'status'            => ['required', 'in:active,retired,resigned,deceased'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cpf_account_no'    => 'CPF account number',
            'pay_scale_step_id' => 'pay scale step',
            'joining_date'      => 'joining date',
            'retirement_date'   => 'retirement date',
            'mobile_number'     => 'mobile number',
        ];
    }
}
