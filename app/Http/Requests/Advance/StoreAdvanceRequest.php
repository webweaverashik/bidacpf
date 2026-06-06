<?php
namespace App\Http\Requests\Advance;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

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
            'approved_amount'   => ['required', 'integer', 'min:1'],
            'interest_rate'     => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                // Default to system setting if not provided, but still validate
            ],
            'installment_count' => [
                'required',
                'integer',
                'min:1',
                'max:' . Setting::maxInstallments(),
            ],
            'remarks'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'       => 'employee',
            'application_date'  => 'application date',
            'approved_amount'   => 'advance amount',
            'interest_rate'     => 'interest rate',
            'installment_count' => 'number of installments',
        ];
    }

    /**
     * Pre-fill interest_rate from system setting if not submitted.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('interest_rate')) {
            $this->merge([
                'interest_rate' => Setting::advanceInterestRate(),
            ]);
        }
    }
}
