<?php
namespace App\Http\Requests\Settlement;

use App\Enums\SettlementType;
use App\Models\Cpf\CpfLedger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settlement = $this->route('settlement');

        return $this->user()->can('cpf_settlement.create') && $settlement->isEditable();
    }

    public function rules(): array
    {
        return [
            'settlement_type'  => ['required', Rule::enum(SettlementType::class)],
            'application_date' => ['required', 'date', 'before_or_equal:today'],
            'settlement_date'  => ['required', 'date'],
            'payee_name'       => ['nullable', 'string', 'max:255'],
            'payee_relation'   => ['nullable', 'string', 'max:100'],
            'payee_detail'     => ['nullable', 'string', 'max:1000'],
            'remarks'          => ['nullable', 'string', 'max:1000'],
            // Optional on edit — only replaced if a new file is supplied.
            'document'         => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
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

            if ($this->input('settlement_type') === SettlementType::DECEASED->value && ! $this->filled('payee_name')) {
                $validator->errors()->add('payee_name', 'A nominee / payee name is required for a deceased settlement.');
            }

            $settlement = $this->route('settlement');

            $lastTxn = CpfLedger::where('employee_id', $settlement->employee_id)->max('transaction_date');
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
