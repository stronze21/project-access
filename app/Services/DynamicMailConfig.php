<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class DynamicMailConfig
{
    public function apply(): void
    {
        if (SystemSetting::get('mail.dynamic_enabled', '0') !== '1') {
            return;
        }

        $mailer = SystemSetting::get('mail.mailer', 'smtp');
        config([
            'mail.default' => $mailer,
            'mail.from.address' => SystemSetting::get('mail.from_address', config('mail.from.address')),
            'mail.from.name' => SystemSetting::get('mail.from_name', config('mail.from.name')),
        ]);

        if ($mailer !== 'smtp') {
            return;
        }

        config([
            'mail.mailers.smtp.host' => SystemSetting::get('mail.host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => (int) SystemSetting::get('mail.port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => SystemSetting::get('mail.username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => $this->password(),
            'mail.mailers.smtp.scheme' => SystemSetting::get('mail.scheme') ?: null,
        ]);
    }

    public function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    private function password(): ?string
    {
        $encrypted = SystemSetting::get('mail.password');
        if (! $encrypted) {
            return config('mail.mailers.smtp.password');
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return null;
        }
    }
}
