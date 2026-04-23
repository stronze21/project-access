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
        ];

        foreach ($settings as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
    }
}
