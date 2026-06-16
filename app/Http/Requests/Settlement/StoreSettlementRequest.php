<?php
namespace App\Http\Requests\Settlement;

use App\Enums\EmployeeStatus;
use App\Enums\SettlementType;
use App\Models\Cpf\CpfLedger;
use App\Models\Employee\Employee;
use App\Services\Cpf\SettlementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cpf_settlement.create');
    }

    public function rules(): array
    {
        return [
            'employee_id'      => ['required', 'integer', 'exists:employees,id'],
            'settlement_type'  => ['required', Rule::enum(SettlementType::class)],
            'application_date' => ['required', 'date', 'before_or_equal:today'],
            'settlement_date'  => ['required', 'date'],
            'payee_name'       => ['nullable', 'string', 'max:255'],
            'payee_relation'   => ['nullable', 'string', 'max:100'],
            'payee_detail'     => ['nullable', 'string', 'max:1000'],
            'remarks'          => ['nullable', 'string', 'max:1000'],
            // Supporting document — retirement order / resignation letter / death certificate.
            'document'         => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'      => 'employee',
            'settlement_type'  => 'settlement type',
            'application_date' => 'application date',
            'settlement_date'  => 'settlement date',
            'payee_name'       => 'payee name',
            'document'         => 'supporting document',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Nominee required for a deceased member.
            if ($this->input('settlement_type') === SettlementType::DECEASED->value && ! $this->filled('payee_name')) {
                $validator->errors()->add('payee_name', 'A nominee / payee name is required for a deceased settlement.');
            }

            $employee = Employee::find($this->input('employee_id'));
            if (! $employee) {
                return;
            }

            // Eligibility guards (mirrored in the service for defence in depth).
            if ($employee->status !== EmployeeStatus::ACTIVE) {
                $validator->errors()->add('employee_id', 'Only active employees can be put up for final settlement.');
            }

            $service = app(SettlementService::class);

            if ($service->hasOpenOrApprovedSettlement($employee)) {
                $validator->errors()->add('employee_id', 'This employee already has a settlement in progress or approved.');
            }

            if ($service->hasPendingAdvanceWork($employee)) {
                $validator->errors()->add('employee_id', 'Resolve the pending advance / recovery approval before settling this employee.');
            }

            // Back-dating guard: a settlement before the last ledger entry would
            // make LedgerService::recalculateFrom produce negative running balances.
            $lastTxn = CpfLedger::where('employee_id', $employee->id)->max('transaction_date');
            if ($lastTxn && Carbon::parse($this->input('settlement_date'))->lt(Carbon::parse($lastTxn))) {
                $validator->errors()->add(
                    'settlement_date',
                    'The settlement date cannot be earlier than the last CPF ledger transaction ('
                        . Carbon::parse($lastTxn)->format('d M Y') . ').'
                );
            }
        });
    }
}
