<?php
namespace App\Http\Requests\Advance;

use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Services\AdvanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advance = $this->route('advance');

        return $this->user()->can('cpf_advance.create') && $advance->isEditable();
    }

    public function rules(): array
    {
        return [
            'application_date'  => ['required', 'date', 'before_or_equal:today'],
            'requested_amount'  => ['required', 'integer', 'min:1'],
            'interest_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:' . Setting::maxInstallments()],
            'remarks'           => ['nullable', 'string', 'max:1000'],
            // Optional on edit — keep the existing file if none uploaded.
            'application'       => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'application_date'  => 'application date',
            'requested_amount'  => 'advance amount',
            'interest_rate'     => 'interest rate',
            'installment_count' => 'number of installments',
            'application'       => 'loan application',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $advance  = $this->route('advance');
            $employee = Employee::find($advance->employee_id);

            if (! $employee) {
                return;
            }

            $eligible = app(AdvanceService::class)->eligibleAmount($employee);

            if ((int) $this->input('requested_amount') > $eligible) {
                $validator->errors()->add(
                    'requested_amount',
                    'The advance amount exceeds the maximum eligible limit of ' .
                    number_format($eligible) . '.'
                );
            }
        });
    }
}
