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

    /** Keys whose value must never appear in the activity log. */
    protected array $secretKeys = ['mail_password'];

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
        $properties = $activity->properties;

        // Redact secret values in both new and old buckets before they're stored.
        if (in_array($this->key, $this->secretKeys, true)) {
            foreach (['attributes', 'old'] as $bucket) {
                $bag = (array) $properties->get($bucket, []);
                if (array_key_exists('value', $bag)) {
                    $bag['value'] = '••••••';
                    $properties   = $properties->put($bucket, $bag);
                }
            }
        }

        // On update, prepend the (immutable) key so the diff stays self-explanatory.
        if ($eventName === 'updated') {
            $attributes = (array) $properties->get('attributes', []);
            $attributes = ['key' => $this->key] + $attributes;
            $properties = $properties->put('attributes', $attributes);
        }

        $activity->properties = $properties;
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
