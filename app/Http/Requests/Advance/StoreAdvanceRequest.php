<?php
namespace App\Http\Requests\Advance;

use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Services\Cpf\AdvanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cpf_advance.create');
    }

    public function rules(): array
    {
        return [
            'employee_id'       => ['required', 'integer', 'exists:employees,id'],
            'application_date'  => ['required', 'date', 'before_or_equal:today'],
            'requested_amount'  => ['required', 'integer', 'min:1'],
            'interest_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:' . Setting::maxInstallments()],
            'remarks'           => ['nullable', 'string', 'max:1000'],
            // Scanned loan application — required on every advance request.
            'application'       => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'       => 'employee',
            'application_date'  => 'application date',
            'requested_amount'  => 'advance amount',
            'interest_rate'     => 'interest rate',
            'installment_count' => 'number of installments',
            'application'       => 'loan application',
        ];
    }

    /**
     * Default rate/installments from settings if the officer left them blank.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('interest_rate')) {
            $merge['interest_rate'] = Setting::advanceInterestRate();
        }

        if (! $this->filled('installment_count')) {
            $merge['installment_count'] = Setting::maxInstallments();
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    /**
     * Enforce the system advance limit (cannot exceed % of current balance).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $employee = Employee::find($this->input('employee_id'));

            if (! $employee) {
                return;
            }

            $eligible = app(AdvanceService::class)->eligibleAmount($employee);

            if ((int) $this->input('requested_amount') > $eligible) {
                $validator->errors()->add(
                    'requested_amount',
                    'The advance amount exceeds the maximum eligible limit of ' .
                    number_format($eligible) . ' (' . Setting::advanceLimitPercentage() .
                    '% of the current CPF balance).'
                );
            }
        });
    }
}
