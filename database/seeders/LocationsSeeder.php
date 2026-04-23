<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationsSeeder extends Seeder
{
    public const REGION_NAME = 'Region I';
    public const REGION_CODE = '01';
    public const PROVINCE_NAME = 'Pangasinan';
    public const PROVINCE_CODE = '0155';
    public const CITY_NAME = 'Alaminos City';
    public const CITY_CODE = '015503';
    public const POSTAL_CODE = '2404';

    public $barangays;
    public $city;
    public $province;
    public $region;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->barangays = self::getBarangays();
        $this->city = self::CITY_NAME;
        $this->province = self::PROVINCE_NAME;
        $this->region = self::REGION_NAME;
    }

    // Getters for other seeders to use
    public static function getBarangays()
    {
        return array_map(
            fn (array $barangay) => $barangay['name'],
            self::getBarangayRecords()
        );
    }

    public static function getBarangayRecords(): array
    {
        if (DB::getSchemaBuilder()->hasTable('refbrgy')) {
            $records = DB::table('refbrgy')
                ->where('citymunCode', self::CITY_CODE)
                ->orderBy('brgyDesc')
                ->get(['brgyCode', 'brgyDesc'])
                ->map(fn ($barangay) => [
                    'code' => $barangay->brgyCode,
                    'name' => self::normalizeBarangayName($barangay->brgyDesc),
                ])
                ->all();

            if (!empty($records)) {
                return $records;
            }
        }

        return [
            ['code' => '015503001', 'name' => 'Alos'],
            ['code' => '015503002', 'name' => 'Amandiego'],
            ['code' => '015503003', 'name' => 'Amangbangan'],
            ['code' => '015503004', 'name' => 'Balangobong'],
            ['code' => '015503005', 'name' => 'Balayang'],
            ['code' => '015503006', 'name' => 'Bisocol'],
            ['code' => '015503007', 'name' => 'Bolaney'],
            ['code' => '015503008', 'name' => 'Baleyadaan'],
            ['code' => '015503009', 'name' => 'Bued'],
            ['code' => '015503010', 'name' => 'Cabatuan'],
            ['code' => '015503011', 'name' => 'Cayucay'],
            ['code' => '015503012', 'name' => 'Dulacac'],
            ['code' => '015503013', 'name' => 'Inerangan'],
            ['code' => '015503014', 'name' => 'Linmansangan'],
            ['code' => '015503015', 'name' => 'Lucap'],
            ['code' => '015503016', 'name' => 'Macatiw'],
            ['code' => '015503017', 'name' => 'Magsaysay'],
            ['code' => '015503018', 'name' => 'Mona'],
            ['code' => '015503019', 'name' => 'Palamis'],
            ['code' => '015503020', 'name' => 'Pangapisan'],
            ['code' => '015503021', 'name' => 'Poblacion'],
            ['code' => '015503022', 'name' => 'Pocalpocal'],
            ['code' => '015503023', 'name' => 'Pogo'],
            ['code' => '015503024', 'name' => 'Polo'],
            ['code' => '015503025', 'name' => 'Quibuar'],
            ['code' => '015503026', 'name' => 'Sabangan'],
            ['code' => '015503029', 'name' => 'San Jose'],
            ['code' => '015503030', 'name' => 'San Roque'],
            ['code' => '015503031', 'name' => 'San Vicente'],
            ['code' => '015503032', 'name' => 'Santa Maria'],
            ['code' => '015503033', 'name' => 'Tanaytay'],
            ['code' => '015503034', 'name' => 'Tangcarang'],
            ['code' => '015503035', 'name' => 'Tawintawin'],
            ['code' => '015503036', 'name' => 'Telbang'],
            ['code' => '015503037', 'name' => 'Victoria'],
            ['code' => '015503038', 'name' => 'Landoc'],
            ['code' => '015503039', 'name' => 'Maawi'],
            ['code' => '015503040', 'name' => 'Pandan'],
            ['code' => '015503041', 'name' => 'San Antonio (R. Magsaysay)'],
        ];
    }

    public static function getCity()
    {
        return self::CITY_NAME;
    }

    public static function getProvince()
    {
        return self::PROVINCE_NAME;
    }

    public static function getRegion()
    {
        return self::REGION_NAME;
    }

    public static function getRegionCode(): string
    {
        return self::REGION_CODE;
    }

    public static function getProvinceCode(): string
    {
        return self::PROVINCE_CODE;
    }

    public static function getCityCode(): string
    {
        return self::CITY_CODE;
    }

    public static function getPostalCode(): string
    {
        return self::POSTAL_CODE;
    }

    private static function normalizeBarangayName(string $name): string
    {
        return preg_replace('/\s+/', ' ', trim($name));
    }
}
