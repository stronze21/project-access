<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get all settings
     *
     * @return array
     */
    public function all(): array
    {
        return Cache::rememberForever('system_settings_all', function () {
            return SystemSetting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get grouped settings for admin panel
     *
     * @return array
     */
    public function allGrouped(): array
    {
        return Cache::rememberForever('system_settings_grouped', function () {
            $settings = SystemSetting::all();
            $grouped = [];

            foreach ($settings as $setting) {
                $grouped[$setting->group][] = $setting;
            }

            return $grouped;
        });
    }

    /**
     * Update multiple settings
     *
     * @param array $data
     * @return bool
     */
    public function updateMultiple(array $data): bool
    {
        $success = true;

        foreach ($data as $key => $value) {
            // Skip non-setting fields like CSRF token
            if (strpos($key, '_token') !== false) {
                continue;
            }

            $setting = SystemSetting::where('key', $key)->first();

            if ($setting) {
                // Handle file uploads
                if ($setting->type === 'file' && !empty($value) && is_object($value)) {
                    $oldValue = $setting->value;

                    // Store specifically in the public disk and system folder
                    $path = $value->store('system', 'public');
                    $setting->value = $path;

                    // Delete old file if it exists and isn't a default
                    if ($oldValue && !in_array($oldValue, ['logo.png', 'favicon.ico']) && Storage::disk('public')->exists($oldValue)) {
                        Storage::disk('public')->delete($oldValue);
                    }
                } else if (!empty($value) || $value === '0') {
                    $setting->value = $value;
                }

                $success = $setting->save() && $success;
            }
        }

        // Clear all settings cache
        SystemSetting::clearCache();
        Cache::forget('system_settings_all');
        Cache::forget('system_settings_grouped');
        Cache::forget('system_settings_public');

        return $success;
    }

    /**
     * Get a file URL for a file-type setting
     *
     * @param string $key
     * @return string
     */
    public function getFileUrl(string $key): string
    {
        $value = SystemSetting::get($key);

        if (empty($value)) {
            return '';
        }

        return Storage::disk('public')->url($value);
    }
}
