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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $self     = (int) $this->input('opening_employee_contribution', 0);
            $govt     = (int) $this->input('opening_government_contribution', 0);
            $interest = (int) $this->input('opening_bank_interest', 0);
            $advance  = (int) $this->input('opening_advance_balance', 0);

            if (($self + $govt + $interest - $advance) < 0) {
                $validator->errors()->add(
                    'opening_advance_balance',
                    'The opening advance balance cannot exceed total opening contributions and interest (net balance would be negative).'
                );
            }
        });
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
