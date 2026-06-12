<?php
namespace App\Enums;

/**
 * Lifecycle of a CPF Final Settlement, mirroring the officer → admin approval
 * flow used by CPF Advances and Contribution Batches.
 *
 *   DRAFT     → officer drafts the settlement (figures previewed, nothing posted)
 *   SUBMITTED → officer forwards for admin review (locked for editing)
 *   APPROVED  → admin approves; the FINAL_SETTLEMENT ledger entry is posted, the
 *               account is closed and the member's status is transitioned
 *   REJECTED  → admin rejects; no financial posting
 */
enum SettlementStatus: string {
    case DRAFT     = 'draft';     // Officer is still editing; nothing posted
    case SUBMITTED = 'submitted'; // Officer submitted; awaiting admin review (locked)
    case APPROVED  = 'approved';  // Admin approved; closing entry posted & account settled
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

    /*
    |--------------------------------------------------------------------------
    | State guards (mirror AdvanceStatus / BatchStatus helper pattern)
    |--------------------------------------------------------------------------
    */

    /** Officer may edit type / dates / payee / remarks / attachment. */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /** Officer may forward a draft for admin review. */
    public function canSubmit(): bool
    {
        return $this === self::DRAFT;
    }

    /** Admin may approve a submitted settlement (posts the closing entry). */
    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    /** Admin may reject a submitted settlement. */
    public function canReject(): bool
    {
        return $this === self::SUBMITTED;
    }

    /** A draft may be deleted by the officer who owns it. */
    public function canDelete(): bool
    {
        return $this === self::DRAFT;
    }

    /** Locked for officer editing once submitted. */
    public function isLocked(): bool
    {
        return in_array($this, [self::SUBMITTED, self::APPROVED, self::REJECTED], true);
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
