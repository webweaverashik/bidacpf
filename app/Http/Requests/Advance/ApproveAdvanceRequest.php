<?php
namespace App\Http\Requests\Advance;

use App\Models\Employee\Employee;
use App\Models\Setting;
use App\Services\Cpf\AdvanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advance = $this->route('advance');

        return $this->user()->can('cpf_advance.approve') && $advance->canApprove();
    }

    public function rules(): array
    {
        return [
            'approved_amount'   => ['required', 'integer', 'min:1'],
            'interest_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:' . Setting::maxInstallments()],
        ];
    }

    public function attributes(): array
    {
        return [
            'approved_amount'   => 'approved amount',
            'interest_rate'     => 'interest rate',
            'installment_count' => 'number of installments',
        ];
    }

    /**
     * Pre-fill from the request's own values if the admin didn't change them.
     */
    protected function prepareForValidation(): void
    {
        $advance = $this->route('advance');
        $merge   = [];

        if (! $this->filled('approved_amount')) {
            $merge['approved_amount'] = $advance->requested_amount;
        }
        if (! $this->filled('interest_rate')) {
            $merge['interest_rate'] = $advance->interest_rate;
        }
        if (! $this->filled('installment_count')) {
            $merge['installment_count'] = $advance->installment_count;
        }

        if ($merge) {
            $this->merge($merge);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $advance  = $this->route('advance');
            $employee = Employee::find($advance->employee_id);

            if (! $employee) {
                return;
            }

            $eligible = app(AdvanceService::class)->eligibleAmount($employee);

            if ((int) $this->input('approved_amount') > $eligible) {
                $validator->errors()->add(
                    'approved_amount',
                    'The approved amount exceeds the maximum eligible limit of ' .
                    number_format($eligible) . '.'
                );
            }
        });
    }
}
