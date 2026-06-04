<?php
namespace App\Enums;

/*
Used for:

cpf_contribution_batches
bank_interest_batches
*/
enum BatchStatus: string {
    case DRAFT    = 'draft';
    case POSTED   = 'posted';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT    => 'Draft',
            self::POSTED   => 'Posted',
            self::REVERSED => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT    => 'badge-warning',
            self::POSTED   => 'badge-success',
            self::REVERSED => 'badge-danger',
        };
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
