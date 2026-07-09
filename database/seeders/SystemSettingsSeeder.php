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
            'mobile_app.name' => ['value' => 'ProjectAccess Mobile', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            'mobile_app.description' => ['value' => 'ProjectAccess Mobile gives residents a direct way to use local digital services, receive announcements, submit requests, and stay connected with city programs.', 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            'mobile_app.version_name' => ['value' => '1.0.0', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            'mobile_app.version_code' => ['value' => '1', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            'mobile_app.release_notes' => ['value' => 'Initial public Android release.', 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            'mobile_app.features' => ['value' => json_encode([
                'Resident account access and profile management',
                'Announcements from the city and barangay offices',
                'Citizen service requests and tracking',
                'Emergency alerts and grievance reporting',
                'Public feedback, complaints, polls, and sentiment features',
            ]), 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            'mobile_app.source_project_path' => ['value' => 'C:\\Users\\HP\\source\\repos\\ProjectAccessApp\\ProjectAccessApp', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => false],
            'mobile_app.apk_path' => ['value' => '', 'group' => 'mobile_app', 'type' => 'file', 'is_public' => true],
            'mobile_app.apk_original_name' => ['value' => '', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            'mobile_app.apk_size' => ['value' => '', 'group' => 'mobile_app', 'type' => 'number', 'is_public' => true],
            'mobile_app.apk_uploaded_at' => ['value' => '', 'group' => 'mobile_app', 'type' => 'datetime', 'is_public' => true],
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
