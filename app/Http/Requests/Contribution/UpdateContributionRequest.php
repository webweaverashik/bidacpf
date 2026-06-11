<?php
namespace App\Http\Requests\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cpf_contribution.create');
    }

    public function rules(): array
    {
        return [
            'basic_salary'            => ['required', 'integer', 'min:0'],
            'employee_contribution'   => ['required', 'integer', 'min:0'],
            'government_contribution' => ['required', 'integer', 'min:0'],
            'remarks'                 => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => 'Please record a reason for this adjustment.',
        ];
    }
}
