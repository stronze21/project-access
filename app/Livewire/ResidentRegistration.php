<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Region;
use Mary\Traits\Toast;
use Livewire\Component;
use App\Models\Province;
use App\Models\Resident;
use App\Models\Household;
use Livewire\Attributes\On;
use App\Models\SystemSetting;
use App\Services\RfidService;
use Livewire\WithFileUploads;
use App\Services\QrCodeService;
use App\Models\CityMunicipality;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResidentRegistration extends Component
{
    use WithFileUploads;
    use Toast;

    // Form data
    #[Validate('required|string|min:2|max:50')]
    public $firstName = '';

    #[Validate('required|string|min:2|max:50')]
    public $lastName = '';

    #[Validate('nullable|string|max:50')]
    public $middleName = '';

    #[Validate('nullable|string|max:10')]
    public $suffix = '';

    #[Validate('required|date|before:today')]
    public $birthDate;

    #[Validate('nullable|string|max:100')]
    public $birthplace = '';

    #[Validate('required|in:male,female,other')]
    public $gender = '';

    #[Validate('required|in:single,married,widowed,divorced,separated,other')]
    public $civilStatus = '';

    // Updated validation rule for Philippine phone numbers
   #[Validate('nullable|string|max:20|philippine_phone')]
    public $contactNumber = '';

    #[Validate('nullable|email|max:100')]
    public $email = '';

    #[Validate('nullable|image|max:2048')]
    public $photo;

    #[Validate('nullable|string|max:100')]
    public $occupation = '';


    #[Validate('nullable|numeric|min:0|max:999999.99')]
    public $monthlyIncome = 0; // Set default value to 0

    #[Validate('nullable|string|max:100')]
    public $educationalAttainment = '';

    // Signature
    #[Validate('nullable|string')]
    public $signature;

    // Signature file upload
    #[Validate('nullable|image|max:2048')]
    public $signatureFile;

    // Temporary signature for modal
    public $tempSignature;

    // Signature method selector
    public $signatureMethod = 'digital';

    // Date Issue
    #[Validate('nullable|date')]
    public $dateIssue;

    // Special Sector
    #[Validate('nullable|string|max:100')]
    public $specialSector = '';

    // Boolean flags
    public $isRegisteredVoter = false;
    public $isPWD = false;
    public $isSeniorCitizen = false;
    public $isSoloParent = false;
    public $isPregnant = false;
    public $isLactating = false;
    public $isIndigenous = false;
    public $is4ps = false;

    // Household related - keep only what's necessary for address
    #[Validate('required|string|min:5|max:255')]
    public $address = '';

    // Location data from the AddressSelector
    public $barangay = '';
    public $cityMunicipality = '';
    public $province = '';
    public $region = '';

    // PSGC Codes
    public $regionCode = '';
    public $provinceCode = '';
    public $cityMunicipalityCode = '';
    public $barangayCode = '';

    // Automatically set relationship to head
    public $relationshipToHead = 'head';

    // QR and RFID
    #[Validate('nullable|string|max:50')]
    public $rfidNumber = '';

    // Notes
    #[Validate('nullable|string|max:1000')]
    public $notes = '';

    // Mode
    public $isEdit = false;
    public $residentId = null;
    public $householdId = null;

    // QR scanning
    public $showQrScanner = false;

    // Search
    #[Validate('nullable|string|min:3|max:100')]
    public $searchTerm = '';

    // Modals
    public $showSignatureModal = false;
    public $showWebcamModal = false;

    // Webcam captured photo
    public $capturedPhoto;

    #[Validate('nullable|string|max:100')]
    public $emergencyContactName = '';

    #[Validate('nullable|string|max:50')]
    public $emergencyContactRelationship = '';

    // Updated validation rule for Philippine phone numbers
   #[Validate('nullable|string|max:20|philippine_phone')]
    public $emergencyContactNumber = '';

    #[Validate('nullable|string|max:50')]
    public $precinctNo = '';

    #[Validate('nullable|string|max:10')]
    public $bloodType = '';
    protected $qrCodeService;
    protected $rfidService;

    /**
     * Constructor
     */
    public function boot(QrCodeService $qrCodeService, RfidService $rfidService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->rfidService = $rfidService;
    }

    // Add a method to normalize phone number before validation
    public function updatedContactNumber($value)
    {
        if (!empty($value)) {
            // Remove any non-numeric characters except the + sign at the beginning
            $phoneNumber = preg_replace('/[^0-9+]/', '', $value);

            // Convert +63 format to 0 format
            if (str_starts_with($phoneNumber, '+63')) {
                $phoneNumber = '0' . substr($phoneNumber, 3);
            }
            // Convert 63 format to 0 format (without +)
            else if (str_starts_with($phoneNumber, '63') && strlen($phoneNumber) >= 11) {
                $phoneNumber = '0' . substr($phoneNumber, 2);
            }
            // Add 0 if number doesn't start with 0
            else if (!str_starts_with($phoneNumber, '0') && strlen($phoneNumber) === 10) {
                $phoneNumber = '0' . $phoneNumber;
            }

            $this->contactNumber = $phoneNumber;
        }
    }

    // The same logic will be useful for emergency contact number
    public function updatedEmergencyContactNumber($value)
    {
        if (!empty($value)) {
            // Remove any non-numeric characters except the + sign at the beginning
            $phoneNumber = preg_replace('/[^0-9+]/', '', $value);

            // Convert +63 format to 0 format
            if (str_starts_with($phoneNumber, '+63')) {
                $phoneNumber = '0' . substr($phoneNumber, 3);
            }
            // Convert 63 format to 0 format (without +)
            else if (str_starts_with($phoneNumber, '63') && strlen($phoneNumber) >= 11) {
                $phoneNumber = '0' . substr($phoneNumber, 2);
            }
            // Add 0 if number doesn't start with 0
            else if (!str_starts_with($phoneNumber, '0') && strlen($phoneNumber) === 10) {
                $phoneNumber = '0' . $phoneNumber;
            }

            $this->emergencyContactNumber = $phoneNumber;
        }
    }

    /**
     * Mount the component.
     */
    public function mount($residentId = null)
    {
        if ($residentId) {
            $this->loadResident($residentId);
        } else {
            $this->birthDate = now()->subYears(18)->format('Y-m-d');
            $this->dateIssue = now()->format('Y-m-d');
            $this->signatureMethod = 'digital'; // Default signature method
        }

        // Set default region, province and city
        $regionInfo = Region::where('regCode', SystemSetting::get('region_code') ?? '02')->first(); // Region II
        $provinceInfo = Province::where('provCode', SystemSetting::get('province_code') ?? '0231')->first(); // ISABELA
        $cityInfo = CityMunicipality::where('citymunCode', SystemSetting::get('municipality_code') ?? '023101')->first(); // ALICIA

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

    /**
     * Handle address update from the AddressSelector component
     */
    #[On('address-updated')]
    public function handleAddressUpdate($addressData)
    {
        // Store all location data from the address selector
        $this->regionCode = $addressData['region']['code'];
        $this->region = $addressData['region']['name'];

        $this->provinceCode = $addressData['province']['code'];
        $this->province = $addressData['province']['name'];

        $this->cityMunicipalityCode = $addressData['city']['code'];
        $this->cityMunicipality = $addressData['city']['name'];

        $this->barangayCode = $addressData['barangay']['code'];
        $this->barangay = $addressData['barangay']['name'];
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
     * Updated lifecycle hook for showWebcamModal
     */
    public function updatedShowWebcamModal($value)
    {
        if ($value) {
            $this->dispatch('webcam-modal-opened');
        } else {
            $this->dispatch('webcam-modal-closed');
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
     * Load a resident for editing.
     */
    public function loadResident($residentId)
    {
        $resident = Resident::findOrFail($residentId);
        $this->residentId = $resident->id;
        $this->isEdit = true;

        // Personal information
        $this->firstName = $resident->first_name;
        $this->lastName = $resident->last_name;
        $this->middleName = $resident->middle_name;
        $this->suffix = $resident->suffix;
        $this->birthDate = $resident->birth_date->format('Y-m-d');
        $this->birthplace = $resident->birthplace;
        $this->bloodType = $resident->blood_type; // Add this line
        $this->gender = $resident->gender;
        $this->civilStatus = $resident->civil_status;
        $this->contactNumber = $resident->contact_number;
        $this->email = $resident->email;
        $this->occupation = $resident->occupation;
        $this->monthlyIncome = $resident->monthly_income;
        $this->educationalAttainment = $resident->educational_attainment;
        $this->signature = $resident->signature;
        $this->dateIssue = $resident->date_issue ? $resident->date_issue->format('Y-m-d') : now()->format('Y-m-d');
        $this->specialSector = $resident->special_sector;

        // Flags
        $this->isRegisteredVoter = $resident->is_registered_voter;
        $this->precinctNo = $resident->precinct_no; // Add this line
        $this->isPWD = $resident->is_pwd;
        $this->isSeniorCitizen = $resident->is_senior_citizen;
        $this->isSoloParent = $resident->is_solo_parent;
        $this->isPregnant = $resident->is_pregnant;
        $this->isLactating = $resident->is_lactating;
        $this->isIndigenous = $resident->is_indigenous;
        $this->is4ps = $resident->is_4ps;

        // Household
        $this->householdId = $resident->household_id;
        $this->relationshipToHead = $resident->relationship_to_head;

        // RFID
        $this->rfidNumber = $resident->rfid_number;

        // Notes
        $this->notes = $resident->notes;

        // Emergency Contact
        $this->emergencyContactName = $resident->emergency_contact_name;
        $this->emergencyContactRelationship = $resident->emergency_contact_relationship;
        $this->emergencyContactNumber = $resident->emergency_contact_number;

        // When editing a resident with an existing signature, use the digital method by default
        if ($resident->signature) {
            $this->signatureMethod = 'digital';
        }

        // If there's a household, load its address data
        if ($this->householdId) {
            $household = Household::find($this->householdId);
            if ($household) {
                $this->address = $household->address;
                $this->barangay = $household->barangay;
                $this->cityMunicipality = $household->city_municipality;
                $this->province = $household->province;
                $this->region = $household->region;
                $this->regionCode = $household->region_code;
                $this->provinceCode = $household->province_code;
                $this->cityMunicipalityCode = $household->city_municipality_code;
                $this->barangayCode = $household->barangay_code;
            }
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
     * Save signature from modal to main signature property with auto-cropping
     */
    public function saveSignature()
    {
        // If the signature is empty, don't process it
        if (!$this->tempSignature) {
            $this->showSignatureModal = false;
            return;
        }

        // The tempSignature should already be cropped by the JavaScript
        // but we'll do a server-side crop as a fallback
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

            // Scan the image pixel by pixel
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    // Get color at this coordinate
                    $rgb = imagecolorat($image, $x, $y);
                    $colors = imagecolorsforindex($image, $rgb);

                    // Check if this pixel is not white/transparent (signature part)
                    // For images with alpha channel
                    if (isset($colors['alpha']) && $colors['alpha'] < 127) {
                        // This is a non-transparent pixel
                        if ($colors['red'] < 245 || $colors['green'] < 245 || $colors['blue'] < 245) {
                            $found = true;
                            $left = min($left, $x);
                            $right = max($right, $x);
                            $top = min($top, $y);
                            $bottom = max($bottom, $y);
                        }
                    }
                    // For images without alpha channel
                    elseif ($colors['red'] < 245 || $colors['green'] < 245 || $colors['blue'] < 245) {
                        $found = true;
                        $left = min($left, $x);
                        $right = max($right, $x);
                        $top = min($top, $y);
                        $bottom = max($bottom, $y);
                    }
                }
            }

            // If no signature found, return the original
            if (!$found) {
                imagedestroy($image);
                return $signatureData;
            }

            // Add padding around the signature
            $padding = 15;
            $left = max(0, $left - $padding);
            $top = max(0, $top - $padding);
            $right = min($width - 1, $right + $padding);
            $bottom = min($height - 1, $bottom + $padding);

            // Calculate new dimensions
            $signatureWidth = $right - $left + 1;
            $signatureHeight = $bottom - $top + 1;

            // Set minimum dimensions - increased for better centering
            $minWidth = 250;
            $minHeight = 100;

            // Always use the greater of minimum or actual size for consistent output
            $finalWidth = max($signatureWidth, $minWidth);
            $finalHeight = max($signatureHeight, $minHeight);

            // Create the cropped image with alpha channel support
            $croppedImage = imagecreatetruecolor($finalWidth, $finalHeight);

            // Enable alpha blending
            imagealphablending($croppedImage, false);
            imagesavealpha($croppedImage, true);

            // Fill with transparent background
            $transparent = imagecolorallocatealpha($croppedImage, 255, 255, 255, 127);
            imagefilledrectangle($croppedImage, 0, 0, $finalWidth, $finalHeight, $transparent);

            // Calculate centering offsets - always center the signature
            $offsetX = floor(($finalWidth - $signatureWidth) / 2);
            $offsetY = floor(($finalHeight - $signatureHeight) / 2);

            // Copy the signature portion with transparency, centered in the output image
            imagecopy($croppedImage, $image, $offsetX, $offsetY, $left, $top, $signatureWidth, $signatureHeight);

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
     * Save the resident.
     */
    public function save()
    {
        // Make sure all modals are closed before validating/submitting
        $this->showSignatureModal = false;
        $this->showWebcamModal = false;

        $this->validate();

        try {
            DB::beginTransaction();

            // Always create a new household for the resident
            if ($this->isEdit && $this->householdId) {
                // Scenario 1: Update existing household
                $household = Household::findOrFail($this->householdId);
                $household->update([
                    'address' => $this->address,
                    'barangay' => $this->barangay,
                    'barangay_code' => $this->barangayCode,
                    'city_municipality' => $this->cityMunicipality,
                    'city_municipality_code' => $this->cityMunicipalityCode,
                    'province' => $this->province,
                    'province_code' => $this->provinceCode,
                    'region' => $this->region,
                    'region_code' => $this->regionCode,
                ]);

            } else {
                // Scenario 2 & 3: Create new household
                $household = Household::create([
                    'household_id' => Household::generateHouseholdId(),
                    'address' => $this->address,
                    'barangay' => $this->barangay,
                    'barangay_code' => $this->barangayCode,
                    'city_municipality' => $this->cityMunicipality,
                    'city_municipality_code' => $this->cityMunicipalityCode,
                    'province' => $this->province,
                    'province_code' => $this->provinceCode,
                    'region' => $this->region,
                    'region_code' => $this->regionCode,
                    'has_electricity' => true,
                    'has_water_supply' => true,
                ]);

                $this->householdId = $household->id;

                // Generate QR code for new household
                $this->qrCodeService->generateHouseholdQrCode($household);
            }

            // Determine if user is a senior citizen based on birth date
            $birthDate = Carbon::parse($this->birthDate);
            $isSeniorCitizen = $birthDate->age >= 60;

            // Ensure monthly income is a valid decimal or set to default value
            $monthlyIncome = is_numeric($this->monthlyIncome) ? $this->monthlyIncome : 0;

            // Create or update resident
            $residentData = [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'middle_name' => $this->middleName,
                'suffix' => $this->suffix,
                'birth_date' => $this->birthDate,
                'birthplace' => $this->birthplace,
                'blood_type' => $this->bloodType,
                'gender' => $this->gender,
                'civil_status' => $this->civilStatus,
                'contact_number' => $this->contactNumber,
                'email' => $this->email,
                'occupation' => $this->occupation,
                'monthly_income' => $monthlyIncome, // Use the sanitized value here
                'educational_attainment' => $this->educationalAttainment,
                'is_registered_voter' => $this->isRegisteredVoter,
                'precinct_no' => $this->precinctNo,
                'is_pwd' => $this->isPWD,
                'is_senior_citizen' => $isSeniorCitizen,
                'is_solo_parent' => $this->isSoloParent,
                'is_pregnant' => $this->isPregnant,
                'is_lactating' => $this->isLactating,
                'is_indigenous' => $this->isIndigenous,
                'is_4ps' => $this->is4ps,
                'special_sector' => $this->specialSector,
                'household_id' => $this->householdId,
                'relationship_to_head' => $this->relationshipToHead,
                'notes' => $this->notes,
                'signature' => $this->signature,
                'date_issue' => $this->dateIssue,
                'emergency_contact_name' => $this->emergencyContactName,
                'emergency_contact_relationship' => $this->emergencyContactRelationship,
                'emergency_contact_number' => $this->emergencyContactNumber,
            ];

            if ($this->isEdit) {
                $resident = Resident::findOrFail($this->residentId);
                $resident->update($residentData);
            } else {
                $residentData['resident_id'] = Resident::generateResidentId();
                $resident = Resident::create($residentData);
            }

            // Handle photo upload
            if ($this->photo) {
                // File upload
                $photoPath = $this->photo->store('resident-photos', 'public');
                $resident->photo_path = $photoPath;
                $resident->save();
            } elseif ($this->capturedPhoto) {
                // Webcam photo - convert data URL to file
                $imageData = explode(',', $this->capturedPhoto);
                if (count($imageData) > 1) {
                    $imageData = base64_decode($imageData[1]);
                    $filename = 'resident-' . $resident->id . '-webcam-' . time() . '.png';
                    $path = 'resident-photos/' . $filename;

                    Storage::disk('public')->put($path, $imageData);
                    $resident->photo_path = $path;
                    $resident->save();
                } else {
                    Log::warning('Invalid webcam photo data format');
                }
            }

            // Generate QR code if not exists
            if (!$resident->qr_code) {
                $this->qrCodeService->generateResidentQrCode($resident);
            }

            // Handle RFID assignment if provided
            if ($this->rfidNumber) {
                $this->rfidService->assignRfidToResident($resident, $this->rfidNumber);
            }

            DB::commit();

            $this->success($this->isEdit ? 'Resident updated successfully!' : 'Resident registered successfully!');

            if (!$this->isEdit) {
                $this->resetForm();
            }

            return redirect()->route('residents.show', $resident->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save resident: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Reset the form.
     */
    public function resetForm()
    {
        $this->reset([
            'firstName',
            'lastName',
            'middleName',
            'suffix',
            'birthDate',
            'birthplace',
            'bloodType', // Add this line
            'gender',
            'civilStatus',
            'contactNumber',
            'email',
            'photo',
            'occupation',
            'monthlyIncome',
            'educationalAttainment',
            'isRegisteredVoter',
            'precinctNo', // Add this line
            'isPWD',
            'isSeniorCitizen',
            'isSoloParent',
            'isPregnant',
            'isLactating',
            'isIndigenous',
            'specialSector',
            'householdId',
            'address',
            'barangay',
            'cityMunicipality',
            'province',
            'region',
            'regionCode',
            'provinceCode',
            'cityMunicipalityCode',
            'barangayCode',
            'rfidNumber',
            'notes',
            'isEdit',
            'residentId',
            'signature',
            'signatureFile',
            'tempSignature',
            'dateIssue',
            'emergencyContactName',
            'emergencyContactRelationship',
            'emergencyContactNumber',
            'signatureMethod',
            'showSignatureModal',
            'showWebcamModal',
            'capturedPhoto',
        ]);

        // Set defaults for new form
        $this->birthDate = now()->subYears(18)->format('Y-m-d');
        $this->dateIssue = now()->format('Y-m-d');
        $this->relationshipToHead = 'head';
        $this->signatureMethod = 'digital';
    }

    /**
     * Search for a resident.
     */
    public function search()
    {
        // This method would search for residents by name, QR code, etc.
        // And redirect to the resident's page if found
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.resident-registration');
    }
}
