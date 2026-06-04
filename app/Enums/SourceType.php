<?php
namespace App\Enums;

enum SourceType: string {
    // This directly supports cpf_ledgers table.
    case OPENING_BALANCE       = 'opening_balance';
    case CONTRIBUTION          = 'contribution';
    case ADVANCE               = 'advance';
    case ADVANCE_RECOVERY      = 'advance_recovery';
    case INTEREST_DISTRIBUTION = 'interest_distribution';
    case MANUAL_ADJUSTMENT     = 'manual_adjustment';
    case FINAL_SETTLEMENT      = 'final_settlement';

    public function label(): string
    {
        return str($this->value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
