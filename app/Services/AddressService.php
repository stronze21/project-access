<?php

namespace App\Services;

class AddressService
{
    /**
     * Get all regions in the Philippines
     *
     * @return array
     */
    public function getRegions(): array
    {
        return [
            'NCR' => 'National Capital Region',
            'CAR' => 'Cordillera Administrative Region',
            'I' => 'Ilocos Region',
            'II' => 'Cagayan Valley',
            'III' => 'Central Luzon',
            'IV-A' => 'CALABARZON',
            'IV-B' => 'MIMAROPA',
            'V' => 'Bicol Region',
            'VI' => 'Western Visayas',
            'VII' => 'Central Visayas',
            'VIII' => 'Eastern Visayas',
            'IX' => 'Zamboanga Peninsula',
            'X' => 'Northern Mindanao',
            'XI' => 'Davao Region',
            'XII' => 'SOCCSKSARGEN',
            'XIII' => 'Caraga',
            'BARMM' => 'Bangsamoro Autonomous Region in Muslim Mindanao',
        ];
    }

    /**
     * Get all provinces grouped by region
     *
     * @return array
     */
    public function getProvincesByRegion(): array
    {
        return [
            'NCR' => [
                'Metro Manila',
            ],
            'CAR' => [
                'Abra',
                'Apayao',
                'Benguet',
                'Ifugao',
                'Kalinga',
                'Mountain Province',
            ],
            'I' => [
                'Ilocos Norte',
                'Ilocos Sur',
                'La Union',
                'Pangasinan',
            ],
            'II' => [
                'Batanes',
                'Cagayan',
                'Isabela',
                'Nueva Vizcaya',
                'Quirino',
            ],
            'III' => [
                'Aurora',
                'Bataan',
                'Bulacan',
                'Nueva Ecija',
                'Pampanga',
                'Tarlac',
                'Zambales',
            ],
            'IV-A' => [
                'Batangas',
                'Cavite',
                'Laguna',
                'Quezon',
                'Rizal',
            ],
            'IV-B' => [
                'Marinduque',
                'Occidental Mindoro',
                'Oriental Mindoro',
                'Palawan',
                'Romblon',
            ],
            'V' => [
                'Albay',
                'Camarines Norte',
                'Camarines Sur',
                'Catanduanes',
                'Masbate',
                'Sorsogon',
            ],
            'VI' => [
                'Aklan',
                'Antique',
                'Capiz',
                'Guimaras',
                'Iloilo',
                'Negros Occidental',
            ],
            'VII' => [
                'Bohol',
                'Cebu',
                'Negros Oriental',
                'Siquijor',
            ],
            'VIII' => [
                'Biliran',
                'Eastern Samar',
                'Leyte',
                'Northern Samar',
                'Samar',
                'Southern Leyte',
            ],
            'IX' => [
                'Zamboanga del Norte',
                'Zamboanga del Sur',
                'Zamboanga Sibugay',
            ],
            'X' => [
                'Bukidnon',
                'Camiguin',
                'Lanao del Norte',
                'Misamis Occidental',
                'Misamis Oriental',
            ],
            'XI' => [
                'Davao de Oro',
                'Davao del Norte',
                'Davao del Sur',
                'Davao Occidental',
                'Davao Oriental',
            ],
            'XII' => [
                'Cotabato',
                'Sarangani',
                'South Cotabato',
                'Sultan Kudarat',
            ],
            'XIII' => [
                'Agusan del Norte',
                'Agusan del Sur',
                'Dinagat Islands',
                'Surigao del Norte',
                'Surigao del Sur',
            ],
            'BARMM' => [
                'Basilan',
                'Lanao del Sur',
                'Maguindanao del Norte',
                'Maguindanao del Sur',
                'Sulu',
                'Tawi-Tawi',
            ],
        ];
    }

    /**
     * Get all provinces as a flat array
     *
     * @return array
     */
    public function getProvinces(): array
    {
        $provinces = [];
        $provincesByRegion = $this->getProvincesByRegion();

        foreach ($provincesByRegion as $regionProvinces) {
            $provinces = array_merge($provinces, $regionProvinces);
        }

        sort($provinces);
        return $provinces;
    }

    /**
     * Get all cities/municipalities by province
     *
     * @return array
     */
    public function getCitiesByProvince(): array
    {
        // Implementation for select provinces - add more as needed
        return [
            'Isabela' => [
                'Alicia',
                'Angadanan',
                'Aurora',
                'Benito Soliven',
                'Burgos',
                'Cabagan',
                'Cabatuan',
                'Cauayan City',
                'Cordon',
                'Delfin Albano',
                'Dinapigue',
                'Divilacan',
                'Echague',
                'Gamu',
                'Ilagan City',
                'Jones',
                'Luna',
                'Maconacon',
                'Mallig',
                'Naguilian',
                'Palanan',
                'Quezon',
                'Quirino',
                'Ramon',
                'Reina Mercedes',
                'Roxas',
                'San Agustin',
                'San Guillermo',
                'San Isidro',
                'San Manuel',
                'San Mariano',
                'San Mateo',
                'San Pablo',
                'Santa Maria',
                'Santiago City',
                'Santo Tomas',
                'Tumauini',
            ],
            // Add more provinces and their cities/municipalities as needed
        ];
    }

    /**
     * Get all cities/municipalities as a flat array
     *
     * @return array
     */
    public function getCities(): array
    {
        $cities = [];
        $citiesByProvince = $this->getCitiesByProvince();

        foreach ($citiesByProvince as $provinceCities) {
            $cities = array_merge($cities, $provinceCities);
        }

        sort($cities);
        return $cities;
    }

    /**
     * Get barangays by city/municipality
     *
     * @return array
     */
    public function getBarangaysByCity(): array
    {
        // Implementation for select cities - add more as needed
        return [

        // Add Alicia, Isabela barangays
        'Alicia' => [
            'Antonino',
            'Araneta',
            'Baguinbin',
            'Bantug-Petines',
            'Bonifacio',
            'Burgos',
            'Calaocan',
            'Dagupan',
            'Inanama',
            'Mabuhay',
            'Malasin',
            'Magsaysay',
            'Nueva Era',
            'Paddad',
            'Rizal',
            'San Antonio',
            'San Fernando',
            'San Juan',
            'Santa Cruz',
            'Santa Maria',
            'Santo Tomas',
            'Victoria',
            'Vijandre',
        ],
            // Add more cities and their barangays as needed
        ];
    }

    /**
     * Get all barangays as a flat array
     *
     * @return array
     */
    public function getBarangays(): array
    {
        $barangays = [];
        $barangaysByCity = $this->getBarangaysByCity();

        foreach ($barangaysByCity as $cityBarangays) {
            $barangays = array_merge($barangays, $cityBarangays);
        }

        sort($barangays);
        return $barangays;
    }

    /**
     * Get province by region code
     *
     * @param string $regionCode
     * @return array
     */
    public function getProvincesByRegion(string $regionCode): array
    {
        $provincesByRegion = $this->getProvincesByRegion();
        return $provincesByRegion[$regionCode] ?? [];
    }

    /**
     * Get cities/municipalities by province name
     *
     * @param string $province
     * @return array
     */
    public function getCitiesByProvince(string $province): array
    {
        $citiesByProvince = $this->getCitiesByProvince();
        return $citiesByProvince[$province] ?? [];
    }

    /**
     * Get barangays by city/municipality name
     *
     * @param string $city
     * @return array
     */
    public function getBarangaysByCity(string $city): array
    {
        $barangaysByCity = $this->getBarangaysByCity();
        return $barangaysByCity[$city] ?? [];
    }

    /**
     * Get the region code for a province
     *
     * @param string $province
     * @return string|null
     */
    public function getRegionByProvince(string $province): ?string
    {
        $provincesByRegion = $this->getProvincesByRegion();

        foreach ($provincesByRegion as $regionCode => $provinces) {
            if (in_array($province, $provinces)) {
                return $regionCode;
            }
        }

        return null;
    }

    /**
     * Get the province for a city/municipality
     *
     * @param string $city
     * @return string|null
     */
    public function getProvinceByCity(string $city): ?string
    {
        $citiesByProvince = $this->getCitiesByProvince();

        foreach ($citiesByProvince as $province => $cities) {
            if (in_array($city, $cities)) {
                return $province;
            }
        }

        return null;
    }

    /**
     * Get the city/municipality for a barangay
     *
     * @param string $barangay
     * @return string|null
     */
    public function getCityByBarangay(string $barangay): ?string
    {
        $barangaysByCity = $this->getBarangaysByCity();

        foreach ($barangaysByCity as $city => $barangays) {
            if (in_array($barangay, $barangays)) {
                return $city;
            }
        }

        return null;
    }

    /**
     * Format a full address
     *
     * @param string $street
     * @param string $barangay
     * @param string $city
     * @param string $province
     * @param string $postalCode
     * @return string
     */
    public function formatAddress(
        string $street = '',
        string $barangay = '',
        string $city = '',
        string $province = '',
        string $postalCode = ''
    ): string {
        $parts = [];

        if (!empty($street)) {
            $parts[] = $street;
        }

        if (!empty($barangay)) {
            $parts[] = "Barangay " . $barangay;
        }

        if (!empty($city)) {
            $parts[] = $city;
        }

        if (!empty($province) && $province !== 'Metro Manila') {
            $parts[] = $province;
        }

        if (!empty($postalCode)) {
            $parts[] = $postalCode;
        }

        return implode(', ', $parts);
    }
}