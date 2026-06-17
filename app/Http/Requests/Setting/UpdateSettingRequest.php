<?php
namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.update');
    }

    protected function prepareForValidation(): void
    {
        $settings = (array) $this->input('settings', []);

        foreach (['otp_enabled', 'notify_app_enabled', 'notify_mail_enabled'] as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
        }

        $this->merge(['settings' => $settings]);
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

            'settings.otp_enabled'                  => ['required', 'boolean'],
            'settings.notify_app_enabled'           => ['required', 'boolean'],
            'settings.notify_mail_enabled'          => ['required', 'boolean'],
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
            'settings.otp_enabled'                  => 'login OTP',
            'settings.notify_app_enabled'           => 'in-app notifications',
            'settings.notify_mail_enabled'          => 'email notifications',
        ];
    }
}
