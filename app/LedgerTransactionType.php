<?php
namespace App;

enum LedgerTransactionType: string {
    case OPENING_BALANCE = 'opening_balance';

    case EMPLOYEE_CONTRIBUTION = 'employee_contribution';

    case GOVERNMENT_CONTRIBUTION = 'government_contribution';

    case ADVANCE_DISBURSEMENT = 'advance_disbursement';

    case ADVANCE_RECOVERY = 'advance_recovery';

    case ADVANCE_INTEREST = 'advance_interest';

    case BANK_INTEREST = 'bank_interest';

    case MANUAL_ADJUSTMENT = 'manual_adjustment';

    case FINAL_SETTLEMENT = 'final_settlement';
}
