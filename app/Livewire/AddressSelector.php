<?php

namespace App\Livewire;

use App\Models\Region;
use Livewire\Component;
use App\Models\Barangay;
use App\Models\Province;
use App\Models\SystemSetting;
use App\Models\CityMunicipality;

class AddressSelector extends Component
{
    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $barangays = [];

    public $selectedRegion = null;
    public $selectedProvince = null;
    public $selectedCity = null;
    public $selectedBarangay = null;

    // Names for display
    public $regionName = '';
    public $provinceName = '';
    public $cityName = '';
    public $barangayName = '';


    // Default codes for Region II (CAGAYAN VALLEY), ISABELA, ALICIA
    protected $defaultRegionCode = '02';     // Region II code
    protected $defaultProvinceCode = '0231'; // ISABELA code
    protected $defaultCityCode = '023101';   // ALICIA code

    public function mount($initialRegionCode = null, $initialProvinceCode = null, $initialCityCode = null, $initialBarangayCode = null)
    {

        $this->regionCode = $initialRegionCode ?? SystemSetting::get('region_code');
        $this->provinceCode = $initialProvinceCode ?? SystemSetting::get('province_code');
        $this->cityMunicipalityCode = $initialCityCode ?? SystemSetting::get('municipality_code');
        // Load all regions
        $this->regions = Region::orderBy('regDesc')->get();

        // Set initial values - use provided values or defaults
        $this->selectedRegion = $initialRegionCode ?? $this->defaultRegionCode;

        // Load provinces for the selected region
        if ($this->selectedRegion) {
            $this->loadProvinces();

            $region = Region::where('regCode', $this->selectedRegion)->first();
            if ($region) {
                $this->regionName = $region->regDesc;
            }

            // Set province - use provided value or default
            $this->selectedProvince = $initialProvinceCode ?? $this->defaultProvinceCode;

            if ($this->selectedProvince) {
                $this->loadCities();

                $province = Province::where('provCode', $this->selectedProvince)->first();
                if ($province) {
                    $this->provinceName = $province->provDesc;
                }

                // Set city - use provided value or default
                $this->selectedCity = $initialCityCode ?? $this->defaultCityCode;

                if ($this->selectedCity) {
                    $this->loadBarangays();

                    $city = CityMunicipality::where('citymunCode', $this->selectedCity)->first();
                    if ($city) {
                        $this->cityName = $city->citymunDesc;
                    }

                    // Set barangay if provided
                    if ($initialBarangayCode) {
                        $this->selectedBarangay = $initialBarangayCode;

                        $barangay = Barangay::where('brgyCode', $initialBarangayCode)->first();
                        if ($barangay) {
                            $this->barangayName = $barangay->brgyDesc;
                        }
                    }
                }
            }
        }

        // Dispatch the initial values to the parent component
        $this->dispatch('address-updated', [
            'region' => [
                'code' => $this->selectedRegion,
                'name' => $this->regionName
            ],
            'province' => [
                'code' => $this->selectedProvince,
                'name' => $this->provinceName
            ],
            'city' => [
                'code' => $this->selectedCity,
                'name' => $this->cityName
            ],
            'barangay' => [
                'code' => $this->selectedBarangay,
                'name' => $this->barangayName
            ]
        ]);
    }

    public function updatedSelectedRegion()
    {
        $this->reset('provinces', 'selectedProvince', 'cities', 'selectedCity', 'barangays', 'selectedBarangay');
        $this->reset('provinceName', 'cityName', 'barangayName');

        $this->loadProvinces();
        $this->updateRegionName();
        $this->dispatchAddressUpdated();
    }

    public function updatedSelectedProvince()
    {
        $this->reset('cities', 'selectedCity', 'barangays', 'selectedBarangay');
        $this->reset('cityName', 'barangayName');

        $this->loadCities();
        $this->updateProvinceName();
        $this->dispatchAddressUpdated();
    }

    public function updatedSelectedCity()
    {
        $this->reset('barangays', 'selectedBarangay', 'barangayName');

        $this->loadBarangays();
        $this->updateCityName();
        $this->dispatchAddressUpdated();
    }

    public function updatedSelectedBarangay()
    {
        $this->updateBarangayName();
        $this->dispatchAddressUpdated();
    }

    private function loadProvinces()
    {
        if ($this->selectedRegion) {
            $this->provinces = Province::where('regCode', $this->selectedRegion)
                ->orderBy('provDesc')
                ->get();
        }
    }

    private function loadCities()
    {
        if ($this->selectedProvince) {
            $this->cities = CityMunicipality::where('provCode', $this->selectedProvince)
                ->orderBy('citymunDesc')
                ->get();
        }
    }

    private function loadBarangays()
    {
        if ($this->selectedCity) {
            $this->barangays = Barangay::where('citymunCode', $this->selectedCity)
                ->orderBy('brgyDesc')
                ->get();
        }
    }

    private function updateRegionName()
    {
        if ($this->selectedRegion) {
            $region = Region::where('regCode', $this->selectedRegion)->first();
            $this->regionName = $region ? $region->regDesc : '';
        } else {
            $this->regionName = '';
        }
    }

    private function updateProvinceName()
    {
        if ($this->selectedProvince) {
            $province = Province::where('provCode', $this->selectedProvince)->first();
            $this->provinceName = $province ? $province->provDesc : '';
        } else {
            $this->provinceName = '';
        }
    }

    private function updateCityName()
    {
        if ($this->selectedCity) {
            $city = CityMunicipality::where('citymunCode', $this->selectedCity)->first();
            $this->cityName = $city ? $city->citymunDesc : '';
        } else {
            $this->cityName = '';
        }
    }

    private function updateBarangayName()
    {
        if ($this->selectedBarangay) {
            $barangay = Barangay::where('brgyCode', $this->selectedBarangay)->first();
            $this->barangayName = $barangay ? $barangay->brgyDesc : '';
        } else {
            $this->barangayName = '';
        }
    }

    private function dispatchAddressUpdated()
    {
        $this->dispatch('address-updated', [
            'region' => [
                'code' => $this->selectedRegion,
                'name' => $this->regionName
            ],
            'province' => [
                'code' => $this->selectedProvince,
                'name' => $this->provinceName
            ],
            'city' => [
                'code' => $this->selectedCity,
                'name' => $this->cityName
            ],
            'barangay' => [
                'code' => $this->selectedBarangay,
                'name' => $this->barangayName
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.address-selector');
    }
}