<?php
namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.update');
    }

    public function rules(): array
    {
        return [
            'settings'                              => ['required', 'array'],

            'settings.employee_contribution_rate'   => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.government_contribution_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.advance_limit_percentage'     => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.advance_interest_rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.max_installments'             => ['required', 'integer', 'min:1', 'max:120'],
            'settings.interest_distribution_months' => ['required', 'array', 'min:1'],
            'settings.interest_distribution_months' => ['required', 'array', 'min:1', 'max:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'settings.employee_contribution_rate'   => 'employee contribution rate',
            'settings.government_contribution_rate' => 'government contribution rate',
            'settings.advance_limit_percentage'     => 'advance limit percentage',
            'settings.advance_interest_rate'        => 'advance interest rate',
            'settings.max_installments'             => 'maximum installments',
            'settings.interest_distribution_months' => 'interest distribution months',
        ];
    }
}
