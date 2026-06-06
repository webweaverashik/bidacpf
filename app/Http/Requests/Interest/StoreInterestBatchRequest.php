<?php
namespace App\Http\Requests\Interest;

use App\Support\FiscalYearService;
use Illuminate\Foundation\Http\FormRequest;

class StoreInterestBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank_interest.create');
    }

    public function rules(): array
    {
        return [
            'distribution_date'     => ['required', 'date', 'before_or_equal:today'],
            'fiscal_year'           => [
                'required',
                'string',
                // Format: YYYY-YYYY e.g. 2025-2026
                'regex:/^\d{4}-\d{4}$/',
            ],
            'total_interest_amount' => ['required', 'integer', 'min:1'],
            'remarks'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'distribution_date'     => 'distribution date',
            'fiscal_year'           => 'fiscal year',
            'total_interest_amount' => 'total interest amount',
        ];
    }

    /**
     * Pre-fill fiscal_year from distribution_date if not provided.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('fiscal_year') && $this->filled('distribution_date')) {
            $this->merge([
                'fiscal_year' => FiscalYearService::fromDate($this->input('distribution_date')),
            ]);
        }
    }
}
