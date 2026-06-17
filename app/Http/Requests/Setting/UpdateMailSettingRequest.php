<?php
namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('Admin');
    }

    public function rules(): array
    {
        return [
            'mailer'            => ['required', 'in:smtp,log'],
            'mail_host'         => ['required_if:mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port'         => ['required_if:mailer,smtp', 'nullable', 'integer', 'between:1,65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            // Blank = keep the currently stored password.
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name'    => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mailer'            => 'mailer',
            'mail_host'         => 'SMTP host',
            'mail_port'         => 'SMTP port',
            'mail_username'     => 'SMTP username',
            'mail_password'     => 'SMTP password',
            'mail_encryption'   => 'encryption',
            'mail_from_address' => 'from address',
            'mail_from_name'    => 'from name',
        ];
    }
}
