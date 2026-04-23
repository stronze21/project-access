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
            'app_name_1' => 'Alaminos City E-Services',
            'app_name_2' => 'Solutions',
            'municipality' => LocationsSeeder::getCity(),
            'province' => LocationsSeeder::getProvince(),
            'region' => LocationsSeeder::getRegion(),
            'municipality_code' => LocationsSeeder::getCityCode(),
            'province_code' => LocationsSeeder::getProvinceCode(),
            'region_code' => LocationsSeeder::getRegionCode(),
            'command_center_name' => 'Alaminos City Command Center',
            'command_center_hotline' => '911',
            'command_center_alternate_hotline' => '(075) 551-2020',
            'command_center_email' => 'commandcenter@alaminoscity.gov.ph',
        ];

        foreach ($settings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
    }
}
