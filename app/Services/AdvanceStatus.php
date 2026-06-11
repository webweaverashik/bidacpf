<?php
namespace App\Enums;

enum AdvanceStatus: string {
    case DRAFT     = 'draft';     // Officer is still editing; nothing forwarded
    case SUBMITTED = 'submitted'; // Officer submitted; awaiting admin review (locked)
    case APPROVED  = 'approved';  // Admin approved; disbursed & outstanding > 0
    case REJECTED  = 'rejected';  // Admin rejected; no financial posting
    case COMPLETED = 'completed'; // Fully repaid & interest benefit credited

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
            self::COMPLETED => 'Completed',
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
            self::COMPLETED => 'badge badge-light-primary',
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
            self::COMPLETED => 'ki-duotone ki-medal-star fs-5',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | State guards (mirror BatchStatus helper pattern)
    |--------------------------------------------------------------------------
    */

    /** Officer may edit amount/rate/installments/remarks/attachment. */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /** Officer may forward a draft for admin review. */
    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    /** Admin may approve a submitted request (posts disbursement). */
    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    /** Admin may reject a submitted request. */
    public function canReject(): bool
    {
        return $this === self::SUBMITTED;
    }

    /** Recovery can be recorded only against an approved (outstanding) advance. */
    public function canRecover(): bool
    {
        return $this === self::APPROVED;
    }

    /** A draft may be deleted by the officer who owns it. */
    public function canDelete(): bool
    {
        return $this === self::DRAFT;
    }

    /** Locked for officer editing once submitted. */
    public function isLocked(): bool
    {
        return in_array($this, [self::SUBMITTED, self::APPROVED, self::REJECTED, self::COMPLETED], true);
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
