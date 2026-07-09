<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileAppReleaseService
{
    public const SOURCE_PROJECT_PATH = 'C:\\Users\\HP\\source\\repos\\ProjectAccessApp\\ProjectAccessApp';

    private const DEFAULT_FEATURES = [
        'Resident account access and profile management',
        'Announcements from the city and barangay offices',
        'Citizen service requests and tracking',
        'Emergency alerts and grievance reporting',
        'Public feedback, complaints, polls, and sentiment features',
    ];

    public function release(): array
    {
        $versionName = SystemSetting::get('mobile_app.version_name', '1.0.0');
        $versionCode = SystemSetting::get('mobile_app.version_code', '1');
        $apkPath = SystemSetting::get('mobile_app.apk_path');
        $apkSize = (int) SystemSetting::get('mobile_app.apk_size', 0);

        return [
            'name' => SystemSetting::get('mobile_app.name', 'ProjectAccess Mobile'),
            'description' => SystemSetting::get(
                'mobile_app.description',
                'ProjectAccess Mobile gives residents a direct way to use local digital services, receive announcements, submit requests, and stay connected with city programs.'
            ),
            'version_name' => $versionName,
            'version_code' => $versionCode,
            'release_notes' => SystemSetting::get('mobile_app.release_notes', 'Initial public Android release.'),
            'features' => $this->features(),
            'source_project_path' => SystemSetting::get('mobile_app.source_project_path', self::SOURCE_PROJECT_PATH),
            'apk_path' => $apkPath,
            'apk_original_name' => SystemSetting::get('mobile_app.apk_original_name'),
            'apk_size' => $apkSize,
            'apk_size_label' => $apkSize > 0 ? $this->formatBytes($apkSize) : null,
            'apk_uploaded_at' => SystemSetting::get('mobile_app.apk_uploaded_at'),
            'has_apk' => filled($apkPath) && Storage::disk('public')->exists($apkPath),
            'download_name' => $this->downloadName($versionName),
        ];
    }

    public function saveDetails(array $data): void
    {
        $this->put('mobile_app.name', $data['name'] ?? 'ProjectAccess Mobile', 'mobile_app', 'text', true);
        $this->put('mobile_app.description', $data['description'] ?? '', 'mobile_app', 'textarea', true);
        $this->put('mobile_app.version_name', $data['version_name'] ?? '', 'mobile_app', 'text', true);
        $this->put('mobile_app.version_code', $data['version_code'] ?? '', 'mobile_app', 'text', true);
        $this->put('mobile_app.release_notes', $data['release_notes'] ?? '', 'mobile_app', 'textarea', true);
        $this->put('mobile_app.features', $this->normalizeFeatures($data['features'] ?? ''), 'mobile_app', 'textarea', true);
        $this->put('mobile_app.source_project_path', $data['source_project_path'] ?? self::SOURCE_PROJECT_PATH, 'mobile_app', 'text', false);

        SystemSetting::clearCache();
    }

    public function saveApk(string $path, string $originalName, int $size): void
    {
        $this->put('mobile_app.apk_path', $path, 'mobile_app', 'file', true);
        $this->put('mobile_app.apk_original_name', $originalName, 'mobile_app', 'text', true);
        $this->put('mobile_app.apk_size', (string) $size, 'mobile_app', 'number', true);
        $this->put('mobile_app.apk_uploaded_at', now()->toDateTimeString(), 'mobile_app', 'datetime', true);

        SystemSetting::clearCache();
    }

    public function clearApk(): void
    {
        foreach ([
            'mobile_app.apk_path',
            'mobile_app.apk_original_name',
            'mobile_app.apk_size',
            'mobile_app.apk_uploaded_at',
        ] as $key) {
            $this->put($key, '', 'mobile_app', 'text', true);
        }

        SystemSetting::clearCache();
    }

    private function features(): array
    {
        $stored = SystemSetting::get('mobile_app.features');

        if (! filled($stored)) {
            return self::DEFAULT_FEATURES;
        }

        $decoded = json_decode((string) $stored, true);

        if (is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($feature) => trim((string) $feature))
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $stored) ?: [])
            ->map(fn ($feature) => trim($feature))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeFeatures(string|array $features): string
    {
        $items = is_array($features)
            ? $features
            : (preg_split('/\r\n|\r|\n/', $features) ?: []);

        return json_encode(
            collect($items)
                ->map(fn ($feature) => trim((string) $feature))
                ->filter()
                ->values()
                ->all()
        ) ?: '[]';
    }

    private function put(string $key, ?string $value, string $group, string $type, bool $isPublic): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'is_public' => $isPublic,
            ]
        );

        cache()->forget("system_settings.{$key}");
        cache()->forget("system_settings_group.{$group}");
        cache()->forget('system_settings_all');
        cache()->forget('system_settings_grouped');
        cache()->forget('system_settings_public');
    }

    private function downloadName(?string $versionName): string
    {
        $version = filled($versionName) ? Str::slug(str_replace('.', '-', (string) $versionName)) : 'latest';

        return "projectaccess-{$version}.apk";
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;

        foreach ($units as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return number_format($size, $size >= 10 ? 1 : 2) . ' ' . $unit;
            }

            $size /= 1024;
        }

        return number_format($size, 1) . ' GB';
    }
}
