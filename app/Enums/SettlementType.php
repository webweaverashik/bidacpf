<?php
namespace App\Enums;

/**
 * The reason a CPF account is being closed out. It drives two things:
 *   1. The employee status the member transitions to on approval.
 *   2. The wording / payee on the generated settlement certificate.
 *
 * Note: the resulting EmployeeStatus cases (RETIRED / RESIGNED / DECEASED)
 * already exist; this enum is the *event* that produces them.
 */
enum SettlementType: string {
    case RETIREMENT  = 'retirement';
    case RESIGNATION = 'resignation';
    case DECEASED    = 'deceased';

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::RETIREMENT  => 'Retirement',
            self::RESIGNATION => 'Resignation',
            self::DECEASED    => 'Deceased',
        };
    }

    /**
     * Metronic/Bootstrap badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::RETIREMENT  => 'badge badge-light-primary',
            self::RESIGNATION => 'badge badge-light-info',
            self::DECEASED    => 'badge badge-light-dark',
        };
    }

    /**
     * Employee status the member moves to once the settlement is approved.
     */
    public function resultingStatus(): EmployeeStatus
    {
        return match ($this) {
            self::RETIREMENT  => EmployeeStatus::RETIRED,
            self::RESIGNATION => EmployeeStatus::RESIGNED,
            self::DECEASED    => EmployeeStatus::DECEASED,
        };
    }

    /**
     * Whether the payout is made to a nominee rather than the member directly.
     */
    public function requiresNominee(): bool
    {
        return $this === self::DECEASED;
    }

    /**
     * Select options.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
