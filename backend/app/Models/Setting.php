<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get a setting value by key with optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storeValue,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
