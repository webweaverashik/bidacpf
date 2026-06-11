<?php
namespace App\Enums;

enum RecoveryStatus: string {
    case DRAFT     = 'draft';     // Officer is still editing; nothing forwarded
    case SUBMITTED = 'submitted'; // Officer submitted; awaiting admin review (locked)
    case APPROVED  = 'approved';  // Admin approved; credit posted, outstanding reduced
    case REJECTED  = 'rejected';  // Admin rejected; no financial posting

    /**
     * Human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SUBMITTED => 'Pending Approval',
            self::APPROVED  => 'Approved',
            self::REJECTED  => 'Rejected',
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
            self::REJECTED  => 'badge badge-light-danger',
        };
    }

    /**
     * Metronic duotone icon.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT     => 'ki-duotone ki-pencil fs-5',
            self::SUBMITTED => 'ki-duotone ki-time fs-5',
            self::APPROVED  => 'ki-duotone ki-check-circle fs-5',
            self::REJECTED  => 'ki-duotone ki-cross-circle fs-5',
        };
    }

    /** Officer may edit amount/deposit info/slip/remarks. */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canReject(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canDelete(): bool
    {
        return $this === self::DRAFT;
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::SUBMITTED, self::APPROVED, self::REJECTED], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
