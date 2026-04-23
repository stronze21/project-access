<?php

namespace App\Livewire;

use Livewire\Component;
use Mary\Traits\Toast;

class BatchImageDownload extends Component
{
    use Toast;

    // Selected resident IDs for batch processing
    public $selectedResidents = [];

    // Image types to include in the download
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

    /**
     * Validate user has selected at least one resident and one image type
     */
    public function validateSelection()
    {
        if (empty($this->selectedResidents)) {
            $this->error('Please select at least one resident.');
            return false;
        }

        $selectedTypes = $this->getSelectedImageTypes();
        if (empty($selectedTypes)) {
            $this->error('Please select at least one image type.');
            return false;
        }

        return true;
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
        if (!$this->validateSelection()) {
            return;
        }

        $residentIds = $this->selectedResidents;
        $imageTypes = $this->getSelectedImageTypes();

        // Redirect to the controller with the form data
        return redirect()->route('residents.batch-images.download', [
            'resident_ids' => $residentIds,
            'image_types' => $imageTypes
        ]);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.batch-image-download');
    }
}
