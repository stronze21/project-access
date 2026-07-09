<?php

namespace App\Services;

use App\Models\SystemSetting;

class ModuleSettings
{
    public const MODULES = [
        'bosesmoto' => [
            'key' => 'modules.bosesmoto.enabled',
            'label' => 'BosesMoTo',
        ],
        'complaints' => [
            'key' => 'modules.bosesmoto.complaints.enabled',
            'label' => 'Complaints',
        ],
        'sentiments' => [
            'key' => 'modules.bosesmoto.sentiments.enabled',
            'label' => 'Sentiments',
        ],
        'polls' => [
            'key' => 'modules.bosesmoto.polls.enabled',
            'label' => 'Polls',
        ],
    ];

    public function enabled(string $module): bool
    {
        $module = $this->normalize($module);

        if (! array_key_exists($module, self::MODULES)) {
            return true;
        }

        if ($module !== 'bosesmoto' && ! $this->enabled('bosesmoto')) {
            return false;
        }

        return $this->toBoolean(SystemSetting::get(self::MODULES[$module]['key'], '1'));
    }

    public function all(): array
    {
        $modules = [];

        foreach (self::MODULES as $name => $config) {
            $modules[$name] = [
                ...$config,
                'enabled' => $this->enabled($name),
            ];
        }

        return $modules;
    }

    public function set(string $module, bool $enabled): void
    {
        $module = $this->normalize($module);

        if (! array_key_exists($module, self::MODULES)) {
            return;
        }

        SystemSetting::updateOrCreate(
            ['key' => self::MODULES[$module]['key']],
            [
                'value' => $enabled ? '1' : '0',
                'group' => 'modules',
                'type' => 'boolean',
                'is_public' => false,
            ]
        );

        SystemSetting::clearCache();
    }

    private function normalize(string $module): string
    {
        return match ($module) {
            'modules.bosesmoto.enabled' => 'bosesmoto',
            'modules.bosesmoto.complaints.enabled' => 'complaints',
            'modules.bosesmoto.sentiments.enabled' => 'sentiments',
            'modules.bosesmoto.polls.enabled' => 'polls',
            default => $module,
        };
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }
}
