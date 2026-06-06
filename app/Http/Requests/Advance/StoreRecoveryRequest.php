<?php
namespace App\Http\Requests\Advance;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cpf_advance.recovery');
    }

    public function rules(): array
    {
        $advance = $this->route('advance');

        return [
            'amount'  => [
                'required',
                'integer',
                'min:1',
                // Cannot recover more than what is outstanding
                'max:' . $advance->outstanding_amount,
            ],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'recovery amount',
        ];
    }

    public function messages(): array
    {
        $advance = $this->route('advance');

        return [
            'amount.max' => "Recovery amount cannot exceed the outstanding balance of " .
            number_format($advance->outstanding_amount) . ".",
        ];
    }
}
