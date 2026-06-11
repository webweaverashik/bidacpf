<?php
namespace App\Enums;

enum BatchStatus: string {
    case DRAFT     = 'draft';     // Auto-generated / regenerated; officer can edit & submit
    case SUBMITTED = 'submitted'; // Officer submitted; awaiting admin approval (locked)
    case APPROVED  = 'approved';  // Admin approved; ledger posted (locked)
    case REVERSED  = 'reversed';  // Admin reversed; reversal ledger entries posted

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SUBMITTED => 'Pending',
            self::APPROVED  => 'Approved',
            self::REVERSED  => 'Reversed',
        };
    }

    /**
     * Metronic/Bootstrap badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT     => 'badge badge-light-warning',
            self::SUBMITTED => 'badge badge-light-info',
            self::APPROVED  => 'badge badge-light-success',
            self::REVERSED  => 'badge badge-light-danger',
        };
    }

    /**
     * Metronic icon.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT     => 'ki-duotone ki-pencil fs-5',
            self::SUBMITTED => 'ki-duotone ki-time fs-5',
            self::APPROVED  => 'ki-duotone ki-check-circle fs-5',
            self::REVERSED  => 'ki-duotone ki-cross-circle fs-5',
        };
    }

    /**
     * Whether contribution rows can be edited / regenerated.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Officer can submit a draft for approval.
     */
    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Admin can approve a submitted batch (posts ledger).
     */
    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    /**
     * Admin can send a submitted batch back to the officer.
     */
    public function canReject(): bool
    {
        return $this === self::SUBMITTED;
    }

    /**
     * Admin can reverse an approved batch (posts reversal ledger entries).
     */
    public function canReverse(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Whether the batch is in a final/locked state for officers.
     */
    public function isLocked(): bool
    {
        return in_array($this, [self::SUBMITTED, self::APPROVED, self::REVERSED], true);
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
