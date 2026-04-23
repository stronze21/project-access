<?php

namespace Database\Seeders;

use App\Models\Household;
use Illuminate\Database\Seeder;

class HouseholdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating households...');

        $barangays = LocationsSeeder::getBarangays();
        $city = LocationsSeeder::getCity();
        $province = LocationsSeeder::getProvince();
        $region = LocationsSeeder::getRegion();

        $dwellingTypes = ['owned', 'rented', 'shared', 'informal', 'other'];

        // Create 150 households distributed across barangays
        for ($i = 1; $i <= 150; $i++) {
            $barangay = $barangays[array_rand($barangays)];
            $dwellingType = $dwellingTypes[array_rand($dwellingTypes)];

            // Calculate monthly income based on dwelling type for some data correlation
            $incomeBase = match($dwellingType) {
                'owned' => rand(15000, 50000),
                'rented' => rand(10000, 30000),
                'shared' => rand(8000, 20000),
                'informal' => rand(5000, 15000),
                'other' => rand(3000, 12000),
            };

            // Add some variation
            $monthlyIncome = $incomeBase + rand(-2000, 2000);

            Household::create([
                'household_id' => 'HH-' . date('Ym') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'address' => 'House #' . rand(1, 999) . ' Street ' . rand(1, 50),
                'barangay' => $barangay,
                'city_municipality' => $city,
                'province' => $province,
                'postal_code' => rand(1000, 9999),
                'region' => $region,
                'dwelling_type' => $dwellingType,
                'monthly_income' => $monthlyIncome,
                'has_electricity' => rand(0, 10) > 1, // 90% have electricity
                'has_water_supply' => rand(0, 10) > 2, // 80% have water
                'is_active' => true,
                'member_count' => 0, // Will be updated when residents are created
                'qr_code' => 'QR-HH-' . strtoupper(substr(md5($i . time()), 0, 10)),
                'notes' => rand(0, 10) > 7 ? 'Special notes for household #' . $i : null, // 30% have notes
            ]);
        }

        $this->command->info('Created ' . Household::count() . ' households.');
    }
}