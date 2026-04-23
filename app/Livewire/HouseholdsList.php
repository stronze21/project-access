<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Region;
use App\Models\Province;
use App\Models\CityMunicipality;
use App\Models\Barangay;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

class HouseholdsList extends Component
{
    use WithPagination;
    use Toast;

    // Search and filters
    #[Url]
    public $search = '';

    #[Url]
    public $barangay = '';

    #[Url]
    public $regionCode = '';

    #[Url]
    public $provinceCode = '';

    #[Url]
    public $cityCode = '';

    #[Url]
    public $barangayCode = '';

    #[Url]
    public $status = 'active';

    #[Url]
    public $sortField = 'created_at';

    #[Url]
    public $sortDirection = 'desc';

    #[Url]
    public $perPage = 10;

    // Flags
    public $showFilters = false;
    public $showQrScanner = false;
    public $showAdvancedFilters = false;

    // List of locations for filter
    public $barangayList = [];
    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $barangays = [];

    // Listeners
    protected $listeners = [
        'scan-result' => 'handleScanResult',
        'address-updated' => 'handleAddressUpdate'
    ];

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->loadBarangayList();
        $this->loadRegions();

        if ($this->regionCode) {
            $this->loadProvinces();

            if ($this->provinceCode) {
                $this->loadCities();

                if ($this->cityCode) {
                    $this->loadBarangays();
                }
            }
        }
    }

    /**
     * Load the list of regions
     */
    public function loadRegions()
    {
        $this->regions = Region::orderBy('regDesc')->get();
    }

    /**
     * Load provinces for the selected region
     */
    public function loadProvinces()
    {
        if ($this->regionCode) {
            $this->provinces = Province::where('regCode', $this->regionCode)
                ->orderBy('provDesc')
                ->get();
        } else {
            $this->provinces = [];
        }

        $this->provinceCode = '';
        $this->cityCode = '';
        $this->barangayCode = '';
        $this->cities = [];
        $this->barangays = [];
    }

    /**
     * Load cities for the selected province
     */
    public function loadCities()
    {
        if ($this->provinceCode) {
            $this->cities = CityMunicipality::where('provCode', $this->provinceCode)
                ->orderBy('citymunDesc')
                ->get();
        } else {
            $this->cities = [];
        }

        $this->cityCode = '';
        $this->barangayCode = '';
        $this->barangays = [];
    }

    /**
     * Load barangays for the selected city
     */
    public function loadBarangays()
    {
        if ($this->cityCode) {
            $this->barangays = Barangay::where('citymunCode', $this->cityCode)
                ->orderBy('brgyDesc')
                ->get();
        } else {
            $this->barangays = [];
        }

        $this->barangayCode = '';
    }

    /**
     * Load the list of barangays for the filter dropdown
     */
    public function loadBarangayList()
    {
        $this->barangayList = Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();
    }

    /**
     * Handle address update from the address selector
     */
    public function handleAddressUpdate($addressData)
    {
        $this->regionCode = $addressData['region']['code'];
        $this->provinceCode = $addressData['province']['code'];
        $this->cityCode = $addressData['city']['code'];
        $this->barangayCode = $addressData['barangay']['code'];
    }

    /**
     * Handle QR scan result
     */
    public function handleScanResult($result)
    {
        if ($result['found'] && $result['type'] === 'household') {
            return redirect()->route('households.show', $result['id']);
        }
    }

    /**
     * Toggle the filters visibility
     */
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * Toggle advanced filters visibility
     */
    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Update region selection
     */
    public function updatedRegionCode()
    {
        $this->loadProvinces();
    }

    /**
     * Update province selection
     */
    public function updatedProvinceCode()
    {
        $this->loadCities();
    }

    /**
     * Update city selection
     */
    public function updatedCityCode()
    {
        $this->loadBarangays();
    }

    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->barangay = '';
        $this->regionCode = '';
        $this->provinceCode = '';
        $this->cityCode = '';
        $this->barangayCode = '';
        $this->status = 'active';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';

        $this->provinces = [];
        $this->cities = [];
        $this->barangays = [];
    }

    /**
     * Sort results by field
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Set household status
     */
    public function setHouseholdStatus($householdId, $status)
    {
        $household = Household::find($householdId);

        if (!$household) {
            $this->error('Household not found');
            return;
        }

        $household->is_active = $status === 'active';
        $household->save();

        $this->success("Household marked as " . ($household->is_active ? 'active' : 'inactive'));
    }

    /**
     * Render the component
     */
    public function render()
    {
        $query = Household::query()
            ->withCount('residents');

        // Apply search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('household_id', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhere('barangay', 'like', '%' . $this->search . '%')
                  ->orWhere('city_municipality', 'like', '%' . $this->search . '%')
                  ->orWhere('qr_code', $this->search);
            });
        }

        // Apply barangay filter (legacy)
        if (!empty($this->barangay)) {
            $query->where('barangay', $this->barangay);
        }

        // Apply PSGC filters
        if (!empty($this->regionCode)) {
            $query->where('region_code', $this->regionCode);

            if (!empty($this->provinceCode)) {
                $query->where('province_code', $this->provinceCode);

                if (!empty($this->cityCode)) {
                    $query->where('city_municipality_code', $this->cityCode);

                    if (!empty($this->barangayCode)) {
                        $query->where('barangay_code', $this->barangayCode);
                    }
                }
            }
        }

        // Apply status filter
        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate results
        $households = $query->paginate($this->perPage);

        return view('livewire.households-list', [
            'households' => $households
        ]);
    }
}