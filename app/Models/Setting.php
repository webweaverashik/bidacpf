<?php
namespace App\Models;

use App\Traits\LogsModelActivity;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class Setting extends BaseModel
{
    use LogsModelActivity;

    protected ?string $auditLogName = 'settings';
    protected ?string $auditLabel   = 'System Settings';
    // protected array $auditAttributes = ['key', 'value', 'description'];

    protected $fillable = ['key', 'value', 'description'];

    /**
     * Always record the (immutable) setting key alongside the value.
     *
     * `key` and `description` never change, so logOnlyDirty() omits them on an
     * update — leaving the diff showing only "Value: old → new" with no hint of
     * which setting it was. Here we prepend the key to the logged attributes so
     * the change record stays self-explanatory later. The key is added to
     * `attributes` only (not `old`), so it renders as plain context rather than
     * a fake "changed" value.
     */
    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        if ($eventName !== 'updated') {
            return;
        }

        $properties = $activity->properties;
        $attributes = (array) $properties->get('attributes', []);

        // Union keeps `key` first, then the actual changed attributes.
        $attributes = ['key' => $this->key] + $attributes;

        $activity->properties = $properties->put('attributes', $attributes);
    }

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
        $attributes = ['value' => $value];

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        static::updateOrCreate(['key' => $key], $attributes);
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
