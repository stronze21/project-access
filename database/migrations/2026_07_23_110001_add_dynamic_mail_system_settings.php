<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['key' => 'mail.dynamic_enabled', 'value' => '0', 'type' => 'boolean'],
            ['key' => 'mail.mailer', 'value' => 'smtp', 'type' => 'text'],
            ['key' => 'mail.host', 'value' => '', 'type' => 'text'],
            ['key' => 'mail.port', 'value' => '587', 'type' => 'number'],
            ['key' => 'mail.username', 'value' => '', 'type' => 'text'],
            ['key' => 'mail.password', 'value' => '', 'type' => 'password'],
            ['key' => 'mail.scheme', 'value' => 'smtp', 'type' => 'text'],
            ['key' => 'mail.from_address', 'value' => '', 'type' => 'email'],
            ['key' => 'mail.from_name', 'value' => 'Project ACCESS', 'type' => 'text'],
        ];

        foreach ($defaults as $setting) {
            DB::table('system_settings')->insertOrIgnore($setting + [
                'group' => 'mail', 'is_public' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
            Cache::forget('system_settings.'.$setting['key']);
        }
        Cache::forget('system_settings_group.mail');
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'mail.dynamic_enabled', 'mail.mailer', 'mail.host', 'mail.port', 'mail.username',
            'mail.password', 'mail.scheme', 'mail.from_address', 'mail.from_name',
        ])->delete();
    }
};
