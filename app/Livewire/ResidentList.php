<?php

namespace App\Livewire;

use App\Models\Resident;
use App\Traits\ComponentAuthorization;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ResidentList extends Component
{
    use WithPagination;
    use Toast;
    use ComponentAuthorization;

    // Search and filters
    #[Url]
    public $search = '';

    #[Url]
    public $barangay = '';

    #[Url]
    public $specialSector = '';

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
    public $showBatchImageModal = false;

    // Portal account filter
    #[Url]
    public $portalAccountStatus = 'all';

    // List of barangays for filter
    public $barangayList = [];

    // List of special sectors for filter
    public $specialSectorList = [];

    // Selected resident for actions
    public $selectedResident = null;

    // Selected residents for batch operations
    public $selectedResidents = [];

    // Batch image download settings
    public $selectedImageTypes = [
        'qr_code' => true,
        'signature' => true,
        'photo' => true
    ];

    // Available image types with friendly labels
    public $availableImageTypes = [
        'qr_code' => 'QR Codes',
        'signature' => 'Signatures',
        'photo' => 'Photos'
    ];

    // Listeners
    protected $listeners = [
        'scan-result' => 'handleScanResult'
    ];

    // Optional: Define required permissions as a property for cleaner code
    protected $requiredPermission = 'view-residents';

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->authorizePermission($this->requiredPermission);
        $this->loadBarangayList();
        $this->loadSpecialSectorList();
    }

    /**
     * Load the list of barangays for the filter dropdown
     */
    public function loadBarangayList()
    {
        $this->barangayList = \App\Models\Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();
    }

    /**
     * Load the list of special sectors for the filter dropdown
     */
    public function loadSpecialSectorList()
    {
        $this->specialSectorList = Resident::select('special_sector')
            ->distinct()
            ->whereNotNull('special_sector')
            ->where('special_sector', '!=', '')
            ->orderBy('special_sector')
            ->pluck('special_sector')
            ->toArray();
    }

    /**
     * Updated search - reset pagination
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Toggle filters visibility
     */
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->barangay = '';
        $this->specialSector = '';
        $this->status = 'active';
        $this->portalAccountStatus = 'all';
        $this->resetPage();
    }

    /**
     * Sort by field
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
     * Toggle QR scanner
     */
    public function toggleQrScanner()
    {
        $this->showQrScanner = !$this->showQrScanner;
    }

    /**
     * Handle QR scan result
     */
    public function handleScanResult($result)
    {
        if ($result['found'] && $result['type'] === 'resident') {
            $this->search = $result['object']['resident_id'];
            $this->showQrScanner = false;
        }
    }

    /**
     * Toggle resident selection for batch operations
     */
    public function toggleResidentSelection($residentId)
    {
        if (in_array($residentId, $this->selectedResidents)) {
            $this->selectedResidents = array_diff($this->selectedResidents, [$residentId]);
        } else {
            $this->selectedResidents[] = $residentId;
        }
    }

    /**
     * Toggle batch image modal
     */
    public function toggleBatchImageModal()
    {
        if (empty($this->selectedResidents)) {
            $this->error('Please select at least one resident.');
            return;
        }

        $this->showBatchImageModal = !$this->showBatchImageModal;
    }

    /**
     * Get the selected image types as an array
     */
    public function getSelectedImageTypes()
    {
        $types = [];
        foreach ($this->selectedImageTypes as $type => $selected) {
            if ($selected) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Download selected images
     */
    public function downloadImages()
    {
        if (empty($this->selectedResidents)) {
            $this->error('Please select at least one resident.');
            return;
        }

        $imageTypes = $this->getSelectedImageTypes();
        if (empty($imageTypes)) {
            $this->error('Please select at least one image type.');
            return;
        }

        // Redirect to the controller with the form data
        return redirect()->route('residents.batch-images.download', [
            'resident_ids' => $this->selectedResidents,
            'image_types' => $imageTypes
        ]);
    }

    /**
     * Set resident status
     */
    public function setResidentStatus($residentId, $status)
    {
        $resident = Resident::find($residentId);

        if (!$resident) {
            $this->error('Resident not found');
            return;
        }

        $resident->is_active = $status === 'active';
        $resident->save();

        $this->success("Resident marked as " . ($resident->is_active ? 'active' : 'inactive'));
    }

    /**
     * Render the component
     */
    public function render()
    {
        $this->authorizePermission($this->requiredPermission);

        $query = Resident::query()
            ->with('household');

        // Apply search

        if (!empty($this->search)) {

            $terms = collect(
                preg_split('/[\s,]+/', $this->search) // split by space OR comma
            )->filter();

            $query->where(function ($q) use ($terms) {

                foreach ($terms as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('first_name', 'like', "%{$term}%")
                            ->orWhere('middle_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
            });
        }

        // Apply barangay filter
        if (!empty($this->barangay)) {
            $query->whereHas('household', function ($q) {
                $q->where('barangay', $this->barangay);
            });
        }

        // Apply special sector filter
        if (!empty($this->specialSector)) {
            $query->where('special_sector', $this->specialSector);
        }

        // Apply status filter
        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        // Apply portal account status filter
        if ($this->portalAccountStatus === 'enabled') {
            $query->whereNotNull('email')
                ->whereNotNull('password');
        } elseif ($this->portalAccountStatus === 'disabled') {
            $query->where(function ($q) {
                $q->whereNull('email')
                    ->orWhereNull('password');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $residents = $query->paginate($this->perPage);

        return view('livewire.resident-list', [
            'residents' => $residents,
        ]);
    }
}
