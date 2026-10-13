<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function getVal(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true) ?? $default,
            default   => $setting->value ?? $default,
        };
    }

    public static function setVal(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        $storedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json'    => is_string($value) ? $value : json_encode($value),
            default   => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => $storedValue,
                'type'        => $type,
                'group'       => $group,
                'description' => $description,
            ]
        );
    }
}
