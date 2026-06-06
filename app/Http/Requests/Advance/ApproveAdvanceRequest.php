<?php
namespace App\Http\Requests\Advance;

use App\Enums\AdvanceStatus;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advance = $this->route('advance');

        // Only pending advances can be approved
        if ($advance->status !== AdvanceStatus::PENDING) {
            return false;
        }

        return $this->user()->can('cpf_advance.approve');
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
