<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public $barangays;
    public $city;
    public $province;
    public $region;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define location data for use in households
        $this->barangays = [
            'Barangay 1',
            'Barangay 2',
            'Barangay 3',
            'Barangay 4',
            'Barangay 5',
            'Barangay 6',
            'Barangay 7',
            'Barangay 8',
            'Barangay 9',
            'Barangay 10',
            'Barangay 11',
            'Barangay 12',
            'Barangay 13',
            'Barangay 14',
            'Barangay 15',
        ];

        $this->city = 'Sample City';
        $this->province = 'Sample Province';
        $this->region = 'Region III';
    }

    // Getters for other seeders to use
    public static function getBarangays()
    {
        $barangays = [
            'Barangay 1',
            'Barangay 2',
            'Barangay 3',
            'Barangay 4',
            'Barangay 5',
            'Barangay 6',
            'Barangay 7',
            'Barangay 8',
            'Barangay 9',
            'Barangay 10',
            'Barangay 11',
            'Barangay 12',
            'Barangay 13',
            'Barangay 14',
            'Barangay 15',
        ];
        return $barangays;
    }

    public static function getCity()
    {
        return $city = 'Sample City';
    }

    public static function getProvince()
    {
        return $province = 'Sample Province';
    }

    public static function getRegion()
    {
        return $region = 'Region III';
    }
}