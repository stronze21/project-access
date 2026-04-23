<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'is_public'
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("system_settings.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        $result = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear the cache for this key
        Cache::forget("system_settings.{$key}");

        return (bool) $result;
    }

    /**
     * Get all settings by group
     *
     * @param string $group
     * @return array
     */
    public static function getByGroup(string $group): array
    {
        $cacheKey = "system_settings_group.{$group}";

        return Cache::rememberForever($cacheKey, function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear all system settings cache
     */
    public static function clearCache(): void
    {
        // Find all setting keys to clear individual caches
        $allKeys = static::pluck('key')->toArray();
        foreach ($allKeys as $key) {
            Cache::forget("system_settings.{$key}");
        }

        // Clear group caches
        $groups = static::distinct()->pluck('group')->toArray();
        foreach ($groups as $group) {
            Cache::forget("system_settings_group.{$group}");
        }
    }
}