<?php
namespace App\Http\Requests\Advance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recovery = $this->route('recovery');

        return $this->user()->can('cpf_advance.recovery') && $recovery->isEditable();
    }

    public function rules(): array
    {
        $recovery = $this->route('recovery');
        $advance  = $recovery->advance;

        return [
            'recovery_date'     => ['required', 'date', 'before_or_equal:today'],
            'amount'            => ['required', 'integer', 'min:1', 'max:' . $advance->outstanding_amount],
            'deposit_date'      => ['nullable', 'date', 'before_or_equal:today'],
            'deposit_reference' => ['nullable', 'string', 'max:100'],
            'bank_name'         => ['nullable', 'string', 'max:150'],
            'remarks'           => ['nullable', 'string', 'max:1000'],
            'deposit_slip'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'recovery_date'     => 'recovery date',
            'amount'            => 'recovery amount',
            'deposit_reference' => 'deposit reference',
            'deposit_slip'      => 'deposit slip',
        ];
    }

    public function messages(): array
    {
        $recovery = $this->route('recovery');

        return [
            'amount.max' => 'Recovery amount cannot exceed the outstanding balance of ' .
                number_format($recovery->advance->outstanding_amount) . '.',
        ];
    }
}
