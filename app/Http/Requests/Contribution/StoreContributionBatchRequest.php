<?php
namespace App\Http\Requests\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cpf_contribution.create');
    }

    public function rules(): array
    {
        return [
            // Expects "YYYY-MM" from a month picker, or a full date like "2026-07-01"
            'contribution_month' => [
                'required',
                'date',
                // Prevent duplicate batch for the same month
                function ($attribute, $value, $fail) {
                    $month  = \Carbon\Carbon::parse($value)->startOfMonth()->toDateString();
                    $exists = \App\Models\CpfContributionBatch::whereYear('contribution_month', \Carbon\Carbon::parse($value)->year)
                        ->whereMonth('contribution_month', \Carbon\Carbon::parse($value)->month)
                        ->exists();

                    if ($exists) {
                        $fail('A contribution batch for this month already exists.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'contribution_month' => 'contribution month',
        ];
    }
}
