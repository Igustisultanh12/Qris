<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'decimal' => (float) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general', ?string $type = null, ?string $description = null): self
    {
        if (is_null($type)) {
            $type = match (true) {
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                is_float($value) => 'decimal',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        $formattedValue = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $formattedValue,
                'type' => $type,
                'description' => $description,
            ]
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }
}
