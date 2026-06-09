<?php
namespace App\Models;

use App\Traits\LogsModelActivity;

class Setting extends BaseModel
{
    use LogsModelActivity;

    protected ?string $auditLogName = 'settings';
    protected ?string $auditLabel   = 'System Settings';
    // protected array $auditAttributes = ['key', 'value', 'description'];

    protected $fillable = ['key', 'value', 'description'];

    /**
     * Get setting value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Save setting.
     */
    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => $value,
                'description' => $description,
            ],
        );
    }

    /**
     * Employee contribution percentage.
     */
    public static function employeeContributionRate(): float
    {
        return (float) static::get('employee_contribution_rate', 10);
    }

    /**
     * Government contribution percentage.
     */
    public static function governmentContributionRate(): float
    {
        return (float) static::get('government_contribution_rate', 8.33);
    }

    /**
     * Maximum advance percentage.
     */
    public static function advanceLimitPercentage(): float
    {
        return (float) static::get('advance_limit_percentage', 80);
    }

    /**
     * Advance interest percentage.
     */
    public static function advanceInterestRate(): float
    {
        return (float) static::get('advance_interest_rate', 5);
    }

    /**
     * Maximum installments.
     */
    public static function maxInstallments(): int
    {
        return (int) static::get('max_installments', 48);
    }
}
