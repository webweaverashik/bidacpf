<?php
namespace App\Enums;

enum BatchStatus: string {
    case DRAFT = 'draft';

    case SUBMITTED = 'submitted';

    case REVERSED = 'reversed';

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::REVERSED  => 'Reversed',
        };
    }

    /**
     * Metronic/Bootstrap badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT     => 'badge badge-warning',
            self::SUBMITTED => 'badge badge-success',
            self::REVERSED  => 'badge badge-danger',
        };
    }

    /**
     * Metronic icon.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT     => 'ki-duotone ki-pencil fs-5',
            self::SUBMITTED => 'ki-duotone ki-check-circle fs-5',
            self::REVERSED  => 'ki-duotone ki-cross-circle fs-5',
        };
    }

    /**
     * Whether records can be edited.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Whether records can be submitted.
     */
    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Whether records can be reversed.
     */
    public function canReverse(): bool
    {
        return $this === self::SUBMITTED;
    }

    /**
     * Select options.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(
                fn(self $case) => [
                    $case->value => $case->label(),
                ],
            )
            ->toArray();
    }
}
