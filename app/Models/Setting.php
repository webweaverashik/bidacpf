<?php
namespace App\Models;

class Setting extends BaseModel
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [];
    }

    public static function value(
        string $key,
        mixed $default = null
    ): mixed {

        $setting = static::query()
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {

            'integer' => (int) $setting->value,

            'float'   => (float) $setting->value,

            'boolean' => filter_var(
                $setting->value,
                FILTER_VALIDATE_BOOLEAN
            ),

            'json'    => json_decode(
                $setting->value,
                true
            ),

            default   => $setting->value,
        };
    }
}
