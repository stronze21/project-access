<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('system_settings')) {
            return;
        }

        $settings = [
            'app_name_1' => ['value' => 'Alaminos City Citizen\'s E-Services', 'group' => 'appearance', 'type' => 'text', 'is_public' => true],
            'app_name_2' => ['value' => 'Solutions', 'group' => 'appearance', 'type' => 'text', 'is_public' => true],
            'municipality' => ['value' => LocationsSeeder::getCity(), 'group' => 'location', 'type' => 'text', 'is_public' => true],
            'province' => ['value' => LocationsSeeder::getProvince(), 'group' => 'location', 'type' => 'text', 'is_public' => true],
            'region' => ['value' => LocationsSeeder::getRegion(), 'group' => 'location', 'type' => 'text', 'is_public' => true],
            'municipality_code' => ['value' => LocationsSeeder::getCityCode(), 'group' => 'location', 'type' => 'text', 'is_public' => false],
            'province_code' => ['value' => LocationsSeeder::getProvinceCode(), 'group' => 'location', 'type' => 'text', 'is_public' => false],
            'region_code' => ['value' => LocationsSeeder::getRegionCode(), 'group' => 'location', 'type' => 'text', 'is_public' => false],
            'command_center_name' => ['value' => 'Alaminos City Command Center', 'group' => 'contact', 'type' => 'text', 'is_public' => true],
            'command_center_hotline' => ['value' => '911', 'group' => 'contact', 'type' => 'text', 'is_public' => true],
            'command_center_alternate_hotline' => ['value' => '(075) 551-2020', 'group' => 'contact', 'type' => 'text', 'is_public' => true],
            'command_center_email' => ['value' => 'commandcenter@alaminoscity.gov.ph', 'group' => 'contact', 'type' => 'email', 'is_public' => true],
            'modules.bosesmoto.enabled' => ['value' => '1', 'group' => 'modules', 'type' => 'boolean', 'is_public' => false],
            'modules.bosesmoto.complaints.enabled' => ['value' => '1', 'group' => 'modules', 'type' => 'boolean', 'is_public' => false],
            'modules.bosesmoto.sentiments.enabled' => ['value' => '1', 'group' => 'modules', 'type' => 'boolean', 'is_public' => false],
            'modules.bosesmoto.polls.enabled' => ['value' => '1', 'group' => 'modules', 'type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $key => $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}
