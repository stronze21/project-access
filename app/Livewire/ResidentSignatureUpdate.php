<?php

namespace App\Livewire;

use Mary\Traits\Toast;
use Livewire\Component;
use App\Models\Resident;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Services\XpPenService;
use Illuminate\Support\Facades\Log;
use App\Rules\XpPenSignatureValidator;
use Illuminate\Validation\ValidationException;

class ResidentSignatureUpdate extends Component
{
    use Toast, WithFileUploads;

    public $residentId;
    public $resident;
    public $signatureMethod = 'tablet';
    public $signature;
    public $tempSignature;
    public $signatureFile;
    public $showSignatureModal = false;
    public $signatureUpdated = false;
    public $tabletConnected = true;

    protected $xpPenService;

    /**
     * Constructor to inject dependencies.
     */
    public function boot(XpPenService $xpPenService)
    {
        $this->xpPenService = $xpPenService;
    }

    /**
     * Mount the component with the resident ID.
     */
    public function mount($residentId)
    {
        $this->residentId = $residentId;
        $this->loadResident();

        // Check if the tablet is connected
        $this->tabletConnected = $this->xpPenService->isTabletConnected();
    }

    /**
     * Define validation rules.
     */
    protected function rules()
    {
        return [
            'signature' => ['nullable', new XpPenSignatureValidator()],
            'signatureFile' => 'nullable|image|max:2048',
        ];
    }

    /**
     * Load the resident data.
     */
    private function loadResident()
    {
        $this->resident = Resident::findOrFail($this->residentId);
        $this->signature = $this->resident->signature;
    }

    /**
     * Updated lifecycle hook for showSignatureModal
     */
    public function updatedShowSignatureModal($value)
    {
        if ($value) {
            // When opening the modal, set tempSignature to current signature
            $this->tempSignature = $this->signature;
            $this->dispatch('signature-modal-opened');
        } else {
            $this->dispatch('signature-modal-closed');
        }
    }

    /**
     * Handle signature file upload
     */
    public function updatedSignatureFile()
    {
        try {
            if ($this->signatureFile) {
                // Convert uploaded image to data URL
                $path = $this->signatureFile->getRealPath();
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $dataUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);

                // Set as the signature
                $this->signature = $dataUrl;
                $this->tempSignature = $dataUrl;

                $this->success('Signature file uploaded successfully!');
            }
        } catch (\Exception $e) {
            Log::error('Error processing signature file: ' . $e->getMessage());
            $this->error('Error processing signature file. Please try again.');
        }
    }

    /**
     * Clear temporary signature
     */
    public function clearTempSignature()
    {
        $this->tempSignature = null;
    }

    /**
     * Save signature from modal to main signature property
     */
    public function saveSignature()
    {
        // If the signature is empty, don't process it
        if (!$this->tempSignature) {
            $this->showSignatureModal = false;
            return;
        }

        // If using digital signature method, auto-crop it
        if ($this->signatureMethod === 'digital' && $this->tempSignature) {
            $this->tempSignature = $this->cropSignature($this->tempSignature);
        }

        // Set the main signature
        $this->signature = $this->tempSignature;
        $this->showSignatureModal = false;

        if ($this->signature) {
            $this->success('Signature captured successfully!');
        }
    }

    #[On('set-cropped-signature')]
    public function setCroppedSignature($signature)
    {
        if (isset($signature['signature']) && !empty($signature['signature'])) {
            $this->tempSignature = $signature['signature'];
        }
    }

    /**
     * Auto-crop a signature to remove excess whitespace
     *
     * @param string $signatureData The signature data URL
     * @return string The cropped signature data URL
     */
    private function cropSignature($signatureData)
    {
        try {
            // Extract the base64 image data
            $parts = explode(',', $signatureData);
            if (count($parts) < 2) {
                return $signatureData; // Return original if invalid format
            }

            // Decode the base64 data
            $imageData = base64_decode($parts[1]);
            if (!$imageData) {
                return $signatureData; // Return original if decoding fails
            }

            // Create image from the decoded data
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return $signatureData; // Return original if can't create image
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Initialize bounds with default values
            $left = $width;
            $right = 0;
            $top = $height;
            $bottom = 0;
            $found = false;

            // Create a temporary image to work with
            $tempImage = imagecreatetruecolor($width, $height);
            imagecopy($tempImage, $image, 0, 0, 0, 0, $width, $height);

            // Get the pixel data
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    // Get color at this coordinate
                    $rgb = imagecolorat($tempImage, $x, $y);
                    $colors = imagecolorsforindex($tempImage, $rgb);

                    // Check if this pixel is not white/transparent
                    // For transparent PNGs, check the alpha channel
                    if (isset($colors['alpha']) && $colors['alpha'] < 127) {
                        $found = true;
                        $left = min($left, $x);
                        $right = max($right, $x);
                        $top = min($top, $y);
                        $bottom = max($bottom, $y);
                    }
                    // For JPGs or non-transparent images, check if the pixel is not white
                    elseif ($colors['red'] < 245 || $colors['green'] < 245 || $colors['blue'] < 245) {
                        $found = true;
                        $left = min($left, $x);
                        $right = max($right, $x);
                        $top = min($top, $y);
                        $bottom = max($bottom, $y);
                    }
                }
            }

            // Clean up the temp image
            imagedestroy($tempImage);

            // If no signature found, return the original
            if (!$found) {
                imagedestroy($image);
                return $signatureData;
            }

            // Add padding around the signature
            $padding = 10;
            $left = max(0, $left - $padding);
            $top = max(0, $top - $padding);
            $right = min($width - 1, $right + $padding);
            $bottom = min($height - 1, $bottom + $padding);

            // Calculate new dimensions
            $newWidth = $right - $left + 1;
            $newHeight = $bottom - $top + 1;

            // Set minimum dimensions
            $minWidth = 150;
            $minHeight = 50;

            // If the cropped area is too small, set to minimum size
            if ($newWidth < $minWidth || $newHeight < $minHeight) {
                // Calculate centering offsets
                $centerX = ($left + $right) / 2;
                $centerY = ($top + $bottom) / 2;

                // Recalculate bounds to maintain the center point
                $newWidth = max($newWidth, $minWidth);
                $newHeight = max($newHeight, $minHeight);

                $left = max(0, $centerX - $newWidth / 2);
                $top = max(0, $centerY - $newHeight / 2);

                // Adjust if going off the edges
                if ($left + $newWidth > $width) {
                    $left = $width - $newWidth;
                }
                if ($top + $newHeight > $height) {
                    $top = $height - $newHeight;
                }
            }

            // Create the cropped image
            $croppedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Create a white background
            $white = imagecolorallocate($croppedImage, 255, 255, 255);
            imagefill($croppedImage, 0, 0, $white);

            // Copy the signature portion
            imagecopy($croppedImage, $image, 0, 0, $left, $top, $newWidth, $newHeight);

            // Convert back to data URL
            ob_start();
            imagepng($croppedImage);
            $croppedData = ob_get_contents();
            ob_end_clean();

            // Clean up
            imagedestroy($image);
            imagedestroy($croppedImage);

            // Return as data URL
            return 'data:image/png;base64,' . base64_encode($croppedData);
        } catch (\Exception $e) {
            // If any error occurs, return the original
            Log::error('Error cropping signature: ' . $e->getMessage());
            return $signatureData;
        }
    }

    /**
     * Save the updated signature to the resident record.
     */
    public function save()
    {
        try {
            // Make sure the modal is closed
            $this->showSignatureModal = false;

            // Validate signature
            try {
                $this->validate();
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $this->error($error);
                    }
                }
                return;
            }

            // Validate that we have a signature
            if (!$this->signature) {
                $this->error('Please capture or upload a signature before saving.');
                return;
            }

            // Use the XpPenService to save the signature
            $success = $this->xpPenService->saveSignature($this->resident, $this->signature);

            if ($success) {
                // Also save as a file for backup
                $this->xpPenService->saveSignatureFile($this->resident, $this->signature);

                $this->signatureUpdated = true;
                $this->success('Signature updated successfully!');
            } else {
                $this->error('Failed to update signature. Please try again.');
            }

        } catch (\Exception $e) {
            Log::error('Failed to save signature: ' . $e->getMessage());
            $this->error('Error saving signature: ' . $e->getMessage());
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.resident-signature-update');
    }
}
