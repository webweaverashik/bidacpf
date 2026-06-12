<?php
namespace App\Http\Requests\Settlement;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approval carries no editable figures — the payout is computed by the service
 * from the ledger at approval time. This request exists to centralise the
 * permission + state guard (admin may approve a submitted settlement only).
 */
class ApproveSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settlement = $this->route('settlement');

        return $this->user()->can('cpf_settlement.approve') && $settlement->canApprove();
    }

    public function rules(): array
    {
        return [];
    }
}
