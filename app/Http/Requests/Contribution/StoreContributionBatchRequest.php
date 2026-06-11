<?php
namespace App\Http\Requests\Contribution;

use App\Models\Cpf\CpfContributionBatch;
use Carbon\Carbon;
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
            'contribution_month' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $date = Carbon::parse($value);

                    // Only the current month may be generated manually.
                    if (! $date->isSameMonth(now())) {
                        $fail('Only the current month (' . now()->format('F Y') . ') batch can be generated manually.');
                        return;
                    }

                    // One batch per month.
                    $exists = CpfContributionBatch::whereYear('contribution_month', $date->year)
                        ->whereMonth('contribution_month', $date->month)
                        ->exists();

                    if ($exists) {
                        $fail('A contribution batch for ' . $date->format('F Y') . ' already exists.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['contribution_month' => 'contribution month'];
    }
}
