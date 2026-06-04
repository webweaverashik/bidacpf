<?php
namespace App\Support;

class MoneyService
{
    /**
     * Apply CPF rounding policy.
     *
     * Standard Half-Up Rounding:
     * 1.0 - 1.4 => Down
     * 1.5 - 1.9 => Up
     */

    /**
     * Standard Half-Up Rounding.
     */
    public static function round(float | int $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Format amount.
     */
    public static function format(int | float $amount): string
    {
        return number_format($amount);
    }

    /**
     * Calculate percentage.
     */
    public static function percentage(int $amount, float $rate): int
    {
        return self::round(($amount * $rate) / 100);
    }
}
