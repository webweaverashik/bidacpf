<?php
namespace App\Support;

use Carbon\Carbon;

class FiscalYearService
{
    /**
     * Current fiscal year.
     */
    public static function current(): string
    {
        return self::fromDate(today());
    }

    /**
     * Fiscal year from date.
     */
    public static function fromDate(Carbon | string $date): string
    {
        $date = Carbon::parse($date);

        if ($date->month >= 7) {
            return sprintf('%s-%s', $date->year, $date->year + 1);
        }

        return sprintf('%s-%s', $date->year - 1, $date->year);
    }

    /**
     * Fiscal year start date.
     */
    public static function startDate(string $fiscalYear): Carbon
    {
        $startYear = explode('-', $fiscalYear)[0];

        return Carbon::create($startYear, 7, 1);
    }

    /**
     * Fiscal year end date.
     */
    public static function endDate(string $fiscalYear): Carbon
    {
        $endYear = explode('-', $fiscalYear)[1];

        return Carbon::create($endYear, 6, 30);
    }
}
