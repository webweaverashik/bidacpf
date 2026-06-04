<?php
namespace App\Enums;

enum LedgerTransactionType: string {
    // Most important enum.
    case OPENING_BALANCE         = 'opening_balance';
    case EMPLOYEE_CONTRIBUTION   = 'employee_contribution';
    case GOVERNMENT_CONTRIBUTION = 'government_contribution';
    case ADVANCE_DISBURSEMENT    = 'advance_disbursement';
    case ADVANCE_RECOVERY        = 'advance_recovery';
    case ADVANCE_INTEREST        = 'advance_interest';
    case BANK_INTEREST           = 'bank_interest';
    case MANUAL_ADJUSTMENT       = 'manual_adjustment';
    case FINAL_SETTLEMENT        = 'final_settlement';

    public function label(): string
    {
        return match ($this) {

            self::OPENING_BALANCE         =>
            'Opening Balance',

            self::EMPLOYEE_CONTRIBUTION   =>
            'Employee Contribution',

            self::GOVERNMENT_CONTRIBUTION =>
            'Government Contribution',

            self::ADVANCE_DISBURSEMENT    =>
            'Advance Disbursement',

            self::ADVANCE_RECOVERY        =>
            'Advance Recovery',

            self::ADVANCE_INTEREST        =>
            'Advance Interest',

            self::BANK_INTEREST           =>
            'Bank Interest',

            self::MANUAL_ADJUSTMENT       =>
            'Manual Adjustment',

            self::FINAL_SETTLEMENT        =>
            'Final Settlement',
        };
    }

    public function isCredit(): bool
    {
        return in_array(
            $this,
            [
                self::OPENING_BALANCE,
                self::EMPLOYEE_CONTRIBUTION,
                self::GOVERNMENT_CONTRIBUTION,
                self::ADVANCE_RECOVERY,
                self::ADVANCE_INTEREST,
                self::BANK_INTEREST,
                self::MANUAL_ADJUSTMENT,
            ]
        );
    }

    public function isDebit(): bool
    {
        return in_array(
            $this,
            [
                self::ADVANCE_DISBURSEMENT,
                self::FINAL_SETTLEMENT,
            ]
        );
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [
                $case->value => $case->label(),
            ])
            ->toArray();
    }
}
