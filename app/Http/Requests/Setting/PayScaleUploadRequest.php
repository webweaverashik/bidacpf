<?php
namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the pay-scale upload form: the meta fields entered alongside the
 * grade/step spreadsheet. The grid itself is validated by PayScaleUploadService.
 */
class PayScaleUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('setting.update');
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'effective_year' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 5)],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after:effective_from'],
            'is_active'      => ['nullable', 'boolean'],
            'file'           => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'effective_year' => 'effective year',
            'effective_from' => 'effective from date',
            'effective_to'   => 'effective to date',
            'is_active'      => 'active status',
            'file'           => 'spreadsheet',
        ];
    }
}
