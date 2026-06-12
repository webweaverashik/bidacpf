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
            'distribution_date'     => [
                'required',
                'date',
                // FY 2025-26 starts 01 Jul 2025 — no earlier cut-offs are offered.
                'after_or_equal:2025-07-01',
                'before_or_equal:today',
                // One batch per cut-off date.
                'unique:bank_interest_batches,distribution_date',
            ],
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
            'distribution_date'     => 'cut-off date',
            'fiscal_year'           => 'fiscal year',
            'total_interest_amount' => 'total interest amount',
        ];
    }

    public function messages(): array
    {
        return [
            'distribution_date.unique'          => 'A distribution batch already exists for this cut-off date.',
            'distribution_date.after_or_equal'  => 'Cut-off dates are available from the 2025-26 fiscal year onward.',
            'distribution_date.before_or_equal' => 'The cut-off date cannot be in the future.',
        ];
    }

    /**
     * Normalise and sanitise input before validation:
     * - trim all string inputs
     * - strip any HTML/tags from free-text (remarks)
     * - reduce the amount to digits only (defeats "1,000", "12abc", etc.)
     * - derive fiscal_year from the cut-off date when not supplied
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('remarks')) {
            $remarks          = trim(strip_tags((string) $this->input('remarks')));
            $merge['remarks'] = $remarks === '' ? null : $remarks;
        }

        if ($this->has('total_interest_amount')) {
            // Keep digits only so the integer rule sees a clean value.
            $merge['total_interest_amount'] = preg_replace('/\D/', '', (string) $this->input('total_interest_amount'));
        }

        if ($this->filled('distribution_date')) {
            $merge['distribution_date'] = trim((string) $this->input('distribution_date'));
        }

        if ($this->filled('fiscal_year')) {
            $merge['fiscal_year'] = trim((string) $this->input('fiscal_year'));
        } elseif ($this->filled('distribution_date')) {
            $merge['fiscal_year'] = FiscalYearService::fromDate($this->input('distribution_date'));
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
