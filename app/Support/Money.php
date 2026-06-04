<?php
namespace App\Support;

class Money
{
    /**
     * Apply CPF rounding policy.
     *
     * Standard Half-Up Rounding:
     * 1.0 - 1.4 => Down
     * 1.5 - 1.9 => Up
     * Usage:    $interestAmount = Money::round($calculatedInterest);
     */

    public static function round(float $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }
}
