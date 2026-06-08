<?php
namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employee.create');
    }

    public function rules(): array
    {
        return [
            // ── Employee fields ──────────────────────────────────
            'cpf_account_no'                  => ['required', 'string', 'max:50', 'unique:employees,cpf_account_no'],
            'name'                            => ['required', 'string', 'max:255'],
            'designation'                     => ['required', 'string', 'max:255'],
            'email'                           => ['nullable', 'email', 'max:255', 'unique:employees,email'],
            'mobile_number'                   => ['nullable', 'string', 'max:20'],
            'joining_date'                    => ['required', 'date'],
            'retirement_date'                 => ['nullable', 'date', 'after:joining_date'],
            'pay_scale_step_id'               => ['required', 'integer', 'exists:pay_scale_steps,id'],
            'status'                          => ['required', 'in:active,retired,resigned,deceased'],
            'photo'                           => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:512'],

            // ── CPF Opening Balance fields ───────────────────────
            'opening_employee_contribution'   => ['required', 'integer', 'min:0'],
            'opening_government_contribution' => ['required', 'integer', 'min:0'],
            'opening_bank_interest'           => ['required', 'integer', 'min:0'],
            'opening_advance_balance'         => ['required', 'integer', 'min:0'],
            'opening_effective_date'          => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cpf_account_no'                  => 'CPF account number',
            'pay_scale_step_id'               => 'basic salary',
            'joining_date'                    => 'joining date',
            'retirement_date'                 => 'retirement date',
            'mobile_number'                   => 'mobile number',
            'opening_employee_contribution'   => 'employee contribution (opening)',
            'opening_government_contribution' => 'government contribution (opening)',
            'opening_bank_interest'           => 'bank interest (opening)',
            'opening_advance_balance'         => 'advance balance (opening)',
            'opening_effective_date'          => 'effective date (opening)',
        ];
    }

    public function messages(): array
    {
        return [
            'opening_employee_contribution.min'   => 'Employee contribution cannot be negative.',
            'opening_government_contribution.min' => 'Government contribution cannot be negative.',
            'opening_bank_interest.min'           => 'Bank interest cannot be negative.',
            'opening_advance_balance.min'         => 'Advance balance cannot be negative.',
        ];
    }
}
