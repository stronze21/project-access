<?php

namespace App\Livewire;

use App\Models\Region;
use Mary\Traits\Toast;
use Livewire\Component;
use App\Models\Province;
use App\Models\Resident;
use App\Models\Household;
use Livewire\Attributes\On;
use App\Services\QrCodeService;
use App\Models\CityMunicipality;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

class HouseholdCreate extends Component
{
    use Toast;

    // Form data
    #[Validate('required|string|min:5|max:255')]
    public $address = '';

    #[Validate('required|string|min:2|max:100')]
    public $barangay = '';

    #[Validate('required|string|min:2|max:100')]
    public $cityMunicipality = '';

    #[Validate('required|string|min:2|max:100')]
    public $province = '';

    #[Validate('nullable|string|max:20')]
    public $postalCode = '';

    #[Validate('nullable|string|max:100')]
    public $region = '';

    // PSGC codes
    public $regionCode = '';
    public $provinceCode = '';
    public $cityMunicipalityCode = '';
    public $barangayCode = '';

    #[Validate('nullable|in:owned,rented,shared,informal,other')]
    public $dwellingType = '';

    #[Validate('nullable|numeric|min:0|max:9999999.99')]
    public $monthlyIncome;

    public $hasElectricity = true;
    public $hasWaterSupply = true;

    #[Validate('nullable|string|max:1000')]
    public $notes = '';

    // Mode
    public $isEdit = false;
    public $householdId = null;

    // Resident selection for head of household
    public $selectedResidentId = null;
    public $availableResidents = [];
    public $searchTerm = '';

    protected $qrCodeService;

    /**
     * Constructor
     */
    public function boot(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Mount the component
     */
    public function mount($householdId = null)
    {
        if ($householdId) {
            $this->loadHousehold($householdId);
        } else {
            // Set defaults for new households
            $regionInfo = Region::where('regCode', '02')->first(); // Region II
            $provinceInfo = Province::where('provCode', '0231')->first(); // ISABELA
            $cityInfo = CityMunicipality::where('citymunCode', '023101')->first(); // ALICIA

            if ($regionInfo) {
                $this->regionCode = $regionInfo->regCode;
                $this->region = $regionInfo->regDesc;
            }

            if ($provinceInfo) {
                $this->provinceCode = $provinceInfo->provCode;
                $this->province = $provinceInfo->provDesc;
            }

            if ($cityInfo) {
                $this->cityMunicipalityCode = $cityInfo->citymunCode;
                $this->cityMunicipality = $cityInfo->citymunDesc;
            }
        }

        if ($householdId) {
            $this->loadHousehold($householdId);
        }

        $this->loadAvailableResidents();
    }

    /**
     * Load household for editing
     */
    public function loadHousehold($householdId)
    {
        $household = Household::findOrFail($householdId);
        $this->householdId = $household->id;
        $this->isEdit = true;

        // Household information
        $this->address = $household->address;
        $this->barangay = $household->barangay;
        $this->cityMunicipality = $household->city_municipality;
        $this->province = $household->province;
        $this->postalCode = $household->postal_code;
        $this->region = $household->region;

        // PSGC codes
        $this->regionCode = $household->region_code;
        $this->provinceCode = $household->province_code;
        $this->cityMunicipalityCode = $household->city_municipality_code;
        $this->barangayCode = $household->barangay_code;

        $this->dwellingType = $household->dwelling_type;
        $this->monthlyIncome = $household->monthly_income;
        $this->hasElectricity = $household->has_electricity;
        $this->hasWaterSupply = $household->has_water_supply;
        $this->notes = $household->notes;

        // Find household head
        $head = $household->residents()->where('relationship_to_head', 'head')->first();
        if ($head) {
            $this->selectedResidentId = $head->id;
        }
    }

    /**
     * Load residents without a household
     */
    public function loadAvailableResidents()
    {
        $query = Resident::where(function($q) {
                $q->whereNull('household_id')
                  ->orWhere('household_id', $this->householdId);
            })
            ->where('is_active', true);

        if (!empty($this->searchTerm)) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('resident_id', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $this->availableResidents = $query->limit(10)->get();
    }

    /**
     * Handle address update from the address selector component
     */
    // Make sure this method matches the event payload
    #[On('address-updated')]
    public function handleAddressUpdate($addressData)
    {
        $this->regionCode = $addressData['region']['code'] ?? '';
        $this->region = $addressData['region']['name'] ?? '';

        $this->provinceCode = $addressData['province']['code'] ?? '';
        $this->province = $addressData['province']['name'] ?? '';

        $this->cityMunicipalityCode = $addressData['city']['code'] ?? '';
        $this->cityMunicipality = $addressData['city']['name'] ?? '';

        $this->barangayCode = $addressData['barangay']['code'] ?? '';
        $this->barangay = $addressData['barangay']['name'] ?? '';
    }

    /**
     * Save household
     */
    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            // Create or update household
            $householdData = [
                'address' => $this->address,
                'barangay' => $this->barangay,
                'barangay_code' => $this->barangayCode,
                'city_municipality' => $this->cityMunicipality,
                'city_municipality_code' => $this->cityMunicipalityCode,
                'province' => $this->province,
                'province_code' => $this->provinceCode,
                'postal_code' => $this->postalCode,
                'region' => $this->region,
                'region_code' => $this->regionCode,
                'dwelling_type' => $this->dwellingType,
                'monthly_income' => $this->monthlyIncome,
                'has_electricity' => $this->hasElectricity,
                'has_water_supply' => $this->hasWaterSupply,
                'notes' => $this->notes,
            ];

            if ($this->isEdit) {
                $household = Household::findOrFail($this->householdId);
                $household->update($householdData);
            } else {
                $householdData['household_id'] = Household::generateHouseholdId();
                $household = Household::create($householdData);
                $this->householdId = $household->id;
            }

            // Generate QR code if it doesn't exist
            if (!$household->qr_code) {
                $this->qrCodeService->generateHouseholdQrCode($household);
            }

            // Set household head if selected
            if ($this->selectedResidentId) {
                $resident = Resident::find($this->selectedResidentId);

                // Reset any existing household heads
                Resident::where('household_id', $household->id)
                    ->where('relationship_to_head', 'head')
                    ->update(['relationship_to_head' => 'member']);
                // Set new household head
                $resident->household_id = $household->id;
                $resident->relationship_to_head = 'head';
                $resident->save();
            }

            // Update household statistics
            $household->updateMemberCount();

            DB::commit();

            $this->success($this->isEdit ? 'Household updated successfully!' : 'Household created successfully!');

            return redirect()->route('households.show', $household->id);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Reset the form
     */
    public function resetForm()
    {
        if (!$this->isEdit) {
            $this->reset([
                'address', 'barangay', 'cityMunicipality', 'province',
                'postalCode', 'region', 'regionCode', 'provinceCode',
                'cityMunicipalityCode', 'barangayCode', 'dwellingType',
                'monthlyIncome', 'hasElectricity', 'hasWaterSupply',
                'notes', 'selectedResidentId'
            ]);
        } else {
            $this->loadHousehold($this->householdId);
        }
    }

    /**
     * Search for available residents
     */
    public function searchResidents()
    {
        $this->loadAvailableResidents();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.household-create');
    }
}