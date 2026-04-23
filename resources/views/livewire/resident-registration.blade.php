<div>
    <x-mary-card title="{{ $isEdit ? 'Edit Resident' : 'New Resident Registration' }}">
        <x-slot:menu>
            @if ($isEdit)
                <x-mary-button link="{{ route('residents.show', $residentId) }}" label="View"
                    class="tagged-color btn-primary" size="sm" />
            @endif
            <x-mary-button link="{{ route('residents.index') }}" label="All Residents"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
        </x-slot:menu>

        <!-- Search and Scan Section -->
        @if (!$isEdit)
            <div class="p-4 mb-6 rounded-lg bg-base-50">
                <div class="flex flex-col space-x-4 md:flex-row">
                    <div class="flex-1">
                        <x-mary-input label="Search Resident" placeholder="Enter name, ID, or scan QR/RFID..."
                            wire:model.blur="searchTerm" icon="o-magnifying-glass"
                            hint="Find existing residents to avoid duplicates" />
                    </div>
                    <div class="flex items-end pb-4 space-x-2">
                        <x-mary-button wire:click="search" class="tagged-color btn-primary" icon="o-magnifying-glass"
                            label="Search" />
                        <x-mary-button wire:click="$toggle('showQrScanner')"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-qr-code">
                            {{ $showQrScanner ? 'Hide Scanner' : 'Scan QR' }}
                        </x-mary-button>
                    </div>
                </div>

                @if ($showQrScanner)
                    <div class="mt-4">
                        <div class="p-4 border rounded-lg bg-base">
                            <h3 class="mb-2 text-lg font-medium">QR Code Scanner</h3>
                            <div class="relative max-w-md mx-auto aspect-video">
                                <div id="qr-reader" class="w-full h-full"></div>

                                <script>
                                    document.addEventListener('livewire:initialized', () => {
                                        const html5QrCode = new Html5Qrcode("qr-reader");
                                        const config = {
                                            fps: 10,
                                            qrbox: {
                                                width: 250,
                                                height: 250
                                            }
                                        };

                                        html5QrCode.start({
                                                facingMode: "environment"
                                            },
                                            config,
                                            (decodedText, decodedResult) => {
                                                // Handle QR code
                                                @this.$wire.set('searchTerm', decodedText);
                                                @this.search();
                                                html5QrCode.stop();
                                                @this.$wire.set('showQrScanner', false);
                                            }
                                        ).catch(err => {
                                            console.error("QR Code scanner error:", err);
                                        });

                                        Livewire.on('qr-scanner-close', () => {
                                            html5QrCode.stop();
                                        });
                                    });
                                </script>
                            </div>
                            <div class="mt-2 text-center">
                                <x-mary-button wire:click="$toggle('showQrScanner')"
                                    class="tagged-color btn-secondary btn-outline btn-secline" size="sm">
                                    Close Scanner
                                </x-mary-button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Personal Information Section -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Personal Information</h3>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-input label="First Name" wire:model="firstName" required
                            error="{{ $errors->first('firstName') }}" />
                        <x-mary-input label="Last Name" wire:model="lastName" required
                            error="{{ $errors->first('lastName') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-input label="Middle Name (optional)" wire:model="middleName"
                            error="{{ $errors->first('middleName') }}" />
                        <x-mary-input label="Suffix (optional)" wire:model="suffix" hint="Jr., Sr., III, etc."
                            error="{{ $errors->first('suffix') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-datetime label="Birth Date" wire:model.live="birthDate" required
                            error="{{ $errors->first('birthDate') }}" />
                        <x-mary-input label="Birthplace" wire:model="birthplace"
                            error="{{ $errors->first('birthplace') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-select label="Gender" wire:model.live="gender" required
                            error="{{ $errors->first('gender') }}" :options="[
                                ['name' => 'Male', 'id' => 'male'],
                                ['name' => 'Female', 'id' => 'female'],
                                ['name' => 'Other', 'id' => 'other'],
                            ]" option-value="id"
                            placeholder="Select gender" />

                        <x-mary-select label="Civil Status" wire:model="civilStatus" required
                            error="{{ $errors->first('civilStatus') }}" :options="[
                                ['name' => 'Single', 'id' => 'single'],
                                ['name' => 'Married', 'id' => 'married'],
                                ['name' => 'Widowed', 'id' => 'widowed'],
                                ['name' => 'Divorced', 'id' => 'divorced'],
                                ['name' => 'Separated', 'id' => 'separated'],
                                ['name' => 'Other', 'id' => 'other'],
                            ]" option-value="id"
                            placeholder="Select civil status" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-input label="Contact Number" wire:model.live="contactNumber"
                            error="{{ $errors->first('contactNumber') }}" />
                        <x-mary-input label="Email Address" wire:model="email" type="email"
                            error="{{ $errors->first('email') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-input label="Occupation" wire:model="occupation"
                            error="{{ $errors->first('occupation') }}" />
                        <x-mary-input label="Monthly Income" wire:model="monthlyIncome" type="number" step="0.01"
                            error="{{ $errors->first('monthlyIncome') }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-select label="Blood Type" wire:model="bloodType"
                            error="{{ $errors->first('bloodType') }}" :options="[
                                ['name' => 'A+', 'id' => 'A+'],
                                ['name' => 'A-', 'id' => 'A-'],
                                ['name' => 'B+', 'id' => 'B+'],
                                ['name' => 'B-', 'id' => 'B-'],
                                ['name' => 'AB+', 'id' => 'AB+'],
                                ['name' => 'AB-', 'id' => 'AB-'],
                                ['name' => 'O+', 'id' => 'O+'],
                                ['name' => 'O-', 'id' => 'O-'],
                                ['name' => 'Unknown', 'id' => 'Unknown'],
                            ]" option-value="id"
                            option-label="name" placeholder="Select blood type" />
                    </div>
                    <div class="mb-4">
                        <x-mary-select label="Educational Attainment" wire:model="educationalAttainment"
                            :options="[
                                ['value' => 'no_formal_education', 'label' => 'No Formal Education'],
                                ['value' => 'elementary', 'label' => 'Elementary School'],
                                ['value' => 'high_school', 'label' => 'High School'],
                                ['value' => 'vocational', 'label' => 'Vocational/Technical Course'],
                                ['value' => 'some_college', 'label' => 'College Undergraduate'],
                                ['value' => 'college', 'label' => 'College Graduate'],
                                ['value' => 'post_graduate', 'label' => 'Post Graduate'],
                            ]" option-value="value" option-label="label"
                            placeholder="Select educational attainment"
                            error="{{ $errors->first('educationalAttainment') }}" />
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="Special Sector" wire:model="specialSector"
                            placeholder="4Ps, PWD, Solo Parent, etc."
                            error="{{ $errors->first('specialSector') }}" />
                    </div>
                    <div class="mt-6">
                        <h4 class="mb-2 font-medium">Emergency Contact</h4>
                        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                            <x-mary-input label="Name" wire:model="emergencyContactName"
                                placeholder="Emergency contact person"
                                error="{{ $errors->first('emergencyContactName') }}" />

                            <x-mary-select label="Relationship" wire:model="emergencyContactRelationship"
                                :options="[
                                    ['value' => 'spouse', 'label' => 'Spouse'],
                                    ['value' => 'parent', 'label' => 'Parent'],
                                    ['value' => 'child', 'label' => 'Child'],
                                    ['value' => 'sibling', 'label' => 'Sibling'],
                                    ['value' => 'relative', 'label' => 'Other Relative'],
                                    ['value' => 'friend', 'label' => 'Friend'],
                                    ['value' => 'neighbor', 'label' => 'Neighbor'],
                                    ['value' => 'other', 'label' => 'Other'],
                                ]" option-value="value" option-label="label"
                                placeholder="Select relationship"
                                error="{{ $errors->first('emergencyContactRelationship') }}" />

                            <x-mary-input label="Contact Number" wire:model.live="emergencyContactNumber"
                                placeholder="Emergency contact number"
                                error="{{ $errors->first('emergencyContactNumber') }}" />
                        </div>
                    </div>
                    <!-- Photo Upload Section with Webcam Option -->
                    <div class="mb-6">
                        <h4 class="mb-2 font-medium">Resident Photo</h4>

                        <div class="flex flex-wrap items-start gap-4">
                            <!-- Photo Preview -->
                            <div
                                class="flex items-center justify-center w-32 h-32 overflow-hidden border rounded bg-gray-50">
                                @if ($photo && !$errors->has('photo'))
                                    <img src="{{ $photo->temporaryUrl() }}" class="object-cover w-full h-full" />
                                @elseif($isEdit && isset($resident) && $resident->photo_path)
                                    <img src="{{ Storage::url($resident->photo_path) }}"
                                        class="object-cover w-full h-full" />
                                @elseif($capturedPhoto)
                                    <img src="{{ $capturedPhoto }}" class="object-cover w-full h-full" />
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                @endif
                            </div>

                            <!-- Upload Options -->
                            <div class="flex-1">
                                <div class="flex flex-col gap-2">
                                    <!-- Webcam Option -->
                                    <div>
                                        <x-mary-button wire:click="$set('showWebcamModal', true)" size="sm"
                                            class="tagged-color btn-secondary btn-outline btn-secline"
                                            icon="o-camera">
                                            Capture from Webcam
                                        </x-mary-button>
                                    </div>
                                    <!-- File Upload Option -->
                                    <div class="mt-2">
                                        <x-mary-file wire:model="photo" hint="Max size: 2MB"
                                            error="{{ $errors->first('photo') }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Webcam Capture Modal -->
                    <x-mary-modal wire:model.live="showWebcamModal" title="Capture Photo from Webcam" max-width="md">
                        <div class="relative">
                            <!-- Video preview area -->
                            <div class="relative overflow-hidden bg-black rounded-lg aspect-video">
                                <video id="webcam-video" class="object-cover w-full h-full" autoplay
                                    playsinline></video>

                                <!-- Canvas for capturing the photo (hidden) -->
                                <canvas id="webcam-canvas" class="hidden"></canvas>

                                <!-- Photo preview (shown after capture) -->
                                <div id="webcam-result" class="absolute inset-0 hidden bg-black">
                                    <img id="captured-image" class="object-cover w-full h-full" src=""
                                        alt="Captured photo">
                                </div>

                                <!-- Loading/error message area -->
                                <div id="webcam-message"
                                    class="absolute inset-0 flex items-center justify-center hidden text-white bg-black bg-opacity-70">
                                    <div class="p-4 text-center">
                                        <span id="webcam-message-text">Loading webcam...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Controls for webcam -->
                            <div class="flex justify-between mt-4 gap-x-2">
                                <!-- Left side controls -->
                                <div>
                                    <x-mary-button id="switch-camera"
                                        class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                        icon="o-arrow-path">
                                        Switch Camera
                                    </x-mary-button>
                                </div>

                                <!-- Right side controls -->
                                <div class="flex gap-x-2">
                                    <div id="capture-controls">
                                        <x-mary-button id="capture-photo" class="tagged-color btn-primary"
                                            icon="o-camera">
                                            Capture Photo
                                        </x-mary-button>
                                    </div>

                                    <div id="review-controls" class="hidden">
                                        <x-mary-button id="retake-photo"
                                            class="tagged-color btn-secondary btn-outline btn-secline"
                                            icon="o-arrow-path">
                                            Retake
                                        </x-mary-button>
                                        <x-mary-button id="accept-photo" class="tagged-color btn-primary"
                                            icon="o-check">
                                            Use Photo
                                        </x-mary-button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('livewire:initialized', function() {
                                // DOM Elements
                                let video = document.getElementById('webcam-video');
                                let canvas = document.getElementById('webcam-canvas');
                                let captureBtn = document.getElementById('capture-photo');
                                let switchBtn = document.getElementById('switch-camera');
                                let retakeBtn = document.getElementById('retake-photo');
                                let acceptBtn = document.getElementById('accept-photo');
                                let resultDiv = document.getElementById('webcam-result');
                                let capturedImage = document.getElementById('captured-image');
                                let captureControls = document.getElementById('capture-controls');
                                let reviewControls = document.getElementById('review-controls');
                                let messageDiv = document.getElementById('webcam-message');
                                let messageText = document.getElementById('webcam-message-text');

                                // Variables
                                let stream = null;
                                let facingMode = 'user'; // start with front camera
                                let constraints = {
                                    video: {
                                        width: {
                                            ideal: 1280
                                        },
                                        height: {
                                            ideal: 720
                                        },
                                        facingMode: facingMode
                                    }
                                };

                                // Show message
                                function showMessage(message) {
                                    messageText.textContent = message;
                                    messageDiv.classList.remove('hidden');
                                }

                                // Hide message
                                function hideMessage() {
                                    messageDiv.classList.add('hidden');
                                }

                                // Initialize webcam
                                async function initializeWebcam() {
                                    try {
                                        showMessage('Initializing webcam...');

                                        if (stream) {
                                            // Stop any existing stream
                                            stream.getTracks().forEach(track => track.stop());
                                        }

                                        // Update constraints with current facing mode
                                        constraints.video.facingMode = facingMode;

                                        // Get media stream
                                        stream = await navigator.mediaDevices.getUserMedia(constraints);
                                        video.srcObject = stream;

                                        // Show video, hide result
                                        video.classList.remove('hidden');
                                        resultDiv.classList.add('hidden');
                                        captureControls.classList.remove('hidden');
                                        reviewControls.classList.add('hidden');

                                        hideMessage();
                                    } catch (error) {
                                        console.error('Error accessing webcam:', error);
                                        showMessage('Error accessing webcam: ' + error.message);
                                    }
                                }

                                // Capture photo
                                function capturePhoto() {
                                    // Set canvas size to match video
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;

                                    // Draw video frame to canvas
                                    let context = canvas.getContext('2d');
                                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                                    // Convert to data URL and show in result
                                    let dataUrl = canvas.toDataURL('image/png');
                                    capturedImage.src = dataUrl;

                                    // Hide video, show result
                                    video.classList.add('hidden');
                                    resultDiv.classList.remove('hidden');
                                    captureControls.classList.add('hidden');
                                    reviewControls.classList.remove('hidden');
                                }

                                // Accept captured photo
                                function acceptPhoto() {
                                    // Get the data URL from the captured image
                                    let dataUrl = capturedImage.src;

                                    // Send to Livewire component
                                    @this.set('capturedPhoto', dataUrl);
                                    @this.set('showWebcamModal', false);

                                    // Clean up
                                    cleanupWebcam();
                                }

                                // Clean up webcam resources
                                function cleanupWebcam() {
                                    if (stream) {
                                        stream.getTracks().forEach(track => track.stop());
                                        stream = null;
                                    }
                                }

                                // Switch camera
                                function switchCamera() {
                                    facingMode = facingMode === 'user' ? 'environment' : 'user';
                                    initializeWebcam();
                                }

                                // Event listeners
                                captureBtn.addEventListener('click', capturePhoto);
                                switchBtn.addEventListener('click', switchCamera);
                                retakeBtn.addEventListener('click', initializeWebcam);
                                acceptBtn.addEventListener('click', acceptPhoto);

                                // Initialize webcam when modal opens
                                Livewire.on('webcam-modal-opened', () => {
                                    initializeWebcam();
                                });

                                // Clean up when modal closes
                                Livewire.on('webcam-modal-closed', () => {
                                    cleanupWebcam();
                                });

                                // Initialize if the modal is already open
                                if (@this.showWebcamModal) {
                                    initializeWebcam();
                                }
                            });
                        </script>

                        <x-slot name="footer">
                            <div class="flex justify-end gap-x-2">
                                <x-mary-button wire:click="$set('showWebcamModal', false)"
                                    class="tagged-color btn-secondary btn-outline btn-secline">
                                    Cancel
                                </x-mary-button>
                            </div>
                        </x-slot>
                    </x-mary-modal>

                    <h4 class="mb-2 font-medium">Status Indicators</h4>
                    <div class="grid grid-cols-2 mb-4 gap-x-4 gap-y-2">
                        <x-mary-checkbox label="Person with Disability (PWD)" wire:model="isPWD" />
                        <x-mary-checkbox label="4Ps" wire:model="is4ps" />
                        @if (isset($birthDate) && Carbon\Carbon::parse($birthDate)->age >= 60)
                            <x-mary-checkbox label="Senior Citizen" wire:model="isSeniorCitizen" />
                        @endif
                        <x-mary-checkbox label="Solo Parent" wire:model="isSoloParent" />
                        @if ($gender != 'male')
                            <x-mary-checkbox label="Pregnant" wire:model="isPregnant" />
                            <x-mary-checkbox label="Lactating Mother" wire:model.live="isLactating" />
                        @endif
                        <x-mary-checkbox label="Indigenous Person" wire:model="isIndigenous" />
                        <x-mary-checkbox label="Registered Voter" wire:model="isRegisteredVoter" />
                        <div class="mb-4">
                            <x-mary-input label="Precinct No." wire:model="precinctNo"
                                placeholder="Enter precinct number if registered voter"
                                error="{{ $errors->first('precinctNo') }}" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="RFID Number" wire:model="rfidNumber"
                            error="{{ $errors->first('rfidNumber') }}" />
                    </div>
                </div>

                <!-- Household Information and Signature Section -->
                <div>
                    <!-- Household Address Section - Simplified -->
                    <div>
                        <h3 class="mb-4 text-lg font-semibold">Household Address</h3>

                        <div class="mb-4">
                            <x-mary-textarea label="House #, Street Name, Purok Name" wire:model="address" required
                                error="{{ $errors->first('address') }}" />
                        </div>

                        <!-- PSGC Address Selector -->
                        <div class="mb-6">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">Location</h4>
                            <livewire:address-selector :initialRegionCode="$regionCode" :initialProvinceCode="$provinceCode" :initialCityCode="$cityMunicipalityCode"
                                :initialBarangayCode="$barangayCode" />
                        </div>
                    </div>

                    <!-- ID Issuance Date -->
                    <div class="mb-4">
                        <x-mary-datetime label="ID Issue Date" wire:model="dateIssue"
                            error="{{ $errors->first('dateIssue') }}" />
                    </div>

                    <!-- Notes Section -->
                    <div class="mt-6">
                        <h4 class="mb-2 font-medium">Additional Notes</h4>
                        <x-mary-textarea wire:model="notes"
                            placeholder="Enter any additional information about this resident..." rows="4"
                            error="{{ $errors->first('notes') }}" />
                    </div>

                    <!-- Signature Modal Trigger Button -->
                    <div class="mt-6">
                        <h3 class="mb-4 text-lg font-semibold">Resident Signature</h3>

                        <div class="flex items-center gap-4">
                            @if ($signature)
                                <div class="max-w-xs p-3 bg-white border rounded">
                                    <img src="{{ $signature }}" class="h-auto max-h-[80px]" />
                                </div>
                            @else
                                <div
                                    class="flex items-center justify-center w-64 h-20 p-3 text-gray-400 border rounded bg-gray-50">
                                    <span>No signature captured</span>
                                </div>
                            @endif

                            <x-mary-button wire:click="$set('showSignatureModal', true)"
                                class="tagged-color btn-primary" size="sm" icon="o-pencil-square">
                                {{ $signature ? 'Change Signature' : 'Capture Signature' }}
                            </x-mary-button>
                        </div>

                        <!-- File upload option for signature -->
                        <div class="mt-4">
                            <h4 class="mb-2 text-sm font-medium">Upload Signature Image</h4>
                            <x-mary-file wire:model="signatureFile" accept="image/*"
                                hint="Upload a signature image (PNG, JPG)"
                                error="{{ $errors->first('signatureFile') }}" />
                        </div>

                        @error('signature')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Signature Capture Modal -->
                    <x-mary-modal wire:model.live="showSignatureModal" title="Capture Signature" max-width="md"
                        persistent box-class="w-11/12 max-w-7xl modal-box">
                        <!-- Signature Method Toggle -->
                        {{-- <div class="mb-4">
                            @php
                                $sigMethods = [
                                    ['id' => 'digital', 'name' => 'Digital Drawing'],
                                    ['id' => 'tablet', 'name' => 'Drawing Tablet'],
                                    ['id' => 'topaz', 'name' => 'Topaz Signature Pad'],
                                ];
                            @endphp

                            <x-mary-radio label="Choose Signature Method" :options="$sigMethods"
                                wire:model.live="signatureMethod"
                                hint="Use mouse/touchscreen or use a connected Topaz signature pad" />
                        </div> --}}


                        <!-- Add this CSS to your blade file to hide watermark text -->
                        <style>
                            /* This CSS completely hides the watermark text in any signature pad */
                            .mary-signature canvas+div,
                            .mary-signature canvas+span,
                            .mary-signature .signature-pad-container .signature-pad-body>div:not(canvas),
                            .mary-signature .m-signature-pad .m-signature-pad--body>div:not(canvas),
                            .mary-signature [data-signature-hint] {
                                display: none !important;
                                opacity: 0 !important;
                                visibility: hidden !important;
                            }

                            /* Ensure the canvas itself remains visible */
                            .mary-signature canvas {
                                display: block !important;
                                visibility: visible !important;
                            }

                            /* Make sure the signature container has proper dimensions */
                            .mary-signature {
                                min-height: 550px;
                            }
                        </style>

                        <!-- Simply continue using the original MaryUI signature component -->
                        @if ($signatureMethod === 'tablet')
                            <div class="mb-4">
                                <div class="overflow-hidden border rounded-lg">
                                    <x-mary-signature wire:model.live="tempSignature" hint=""
                                        class="h-auto min-h-[550px]" height="554" />
                                </div>
                            </div>
                        @endif
                        <!-- Digital Signature Pad (MaryUI) -->
                        @if ($signatureMethod === 'digital')
                            <div class="relative mb-4 signature-container">
                                <!-- Add a custom wrapper with relative positioning -->
                                <div id="signature-canvas-container" class="relative overflow-hidden border rounded">
                                    <x-mary-signature wire:model.live="tempSignature"
                                        hint="Sign using mouse or touch screen" height="800"
                                        class="h-auto max-h-[550px] signature-canvas" id="signature-pad" />
                                </div>


                                <script>
                                    document.addEventListener('livewire:initialized', function() {
                                        // Find the signature canvas in MaryUI signature component
                                        const findSignatureCanvas = function() {
                                            // Look within the signature modal
                                            const modal = document.querySelector('.mary-modal');
                                            if (!modal) return null;

                                            // Find the canvas within the signature component
                                            return modal.querySelector('canvas');
                                        };

                                        // Auto-crop the signature directly in the browser before saving
                                        // This provides visual feedback to the user before sending to the server
                                        const autoCropSignature = function(canvas) {
                                            if (!canvas) return null;

                                            const ctx = canvas.getContext('2d');
                                            const width = canvas.width;
                                            const height = canvas.height;

                                            // Get the image data to analyze
                                            const imageData = ctx.getImageData(0, 0, width, height);
                                            const data = imageData.data;

                                            // Initialize bounds
                                            let left = width;
                                            let right = 0;
                                            let top = height;
                                            let bottom = 0;
                                            let foundSignature = false;

                                            // Scan the canvas pixel by pixel
                                            for (let y = 0; y < height; y++) {
                                                for (let x = 0; x < width; x++) {
                                                    const idx = (y * width + x) * 4;
                                                    // Get RGB+A values
                                                    const r = data[idx];
                                                    const g = data[idx + 1];
                                                    const b = data[idx + 2];
                                                    const a = data[idx + 3]; // Alpha channel

                                                    // If this pixel has any opacity and is not pure white
                                                    // Check both alpha channel and color values to catch all signature pixels
                                                    if (a > 0 && (r < 245 || g < 245 || b < 245)) {
                                                        foundSignature = true;
                                                        left = Math.min(left, x);
                                                        right = Math.max(right, x);
                                                        top = Math.min(top, y);
                                                        bottom = Math.max(bottom, y);
                                                    }
                                                }
                                            }

                                            // If no signature found, return null
                                            if (!foundSignature) return null;

                                            // Add padding
                                            const padding = 15;
                                            left = Math.max(0, left - padding);
                                            top = Math.max(0, top - padding);
                                            right = Math.min(width - 1, right + padding);
                                            bottom = Math.min(height - 1, bottom + padding);

                                            // Get signature dimensions
                                            const signatureWidth = right - left + 1;
                                            const signatureHeight = bottom - top + 1;

                                            // Minimum dimensions
                                            const minWidth = 250; // Increased for better centering
                                            const minHeight = 100; // Increased for better centering

                                            // Final dimensions - always use the larger of minimum or actual size
                                            // This ensures we always have a consistent output size
                                            const finalWidth = Math.max(signatureWidth, minWidth);
                                            const finalHeight = Math.max(signatureHeight, minHeight);

                                            // Create a new canvas for the cropped signature
                                            const croppedCanvas = document.createElement('canvas');
                                            croppedCanvas.width = finalWidth;
                                            croppedCanvas.height = finalHeight;

                                            // Get the context for the new canvas
                                            const croppedCtx = croppedCanvas.getContext('2d');

                                            // Important: Make the background transparent
                                            croppedCtx.clearRect(0, 0, finalWidth, finalHeight);

                                            // Calculate centering offsets - this will center the signature
                                            // even if it's smaller than the minimum dimensions
                                            const offsetX = Math.floor((finalWidth - signatureWidth) / 2);
                                            const offsetY = Math.floor((finalHeight - signatureHeight) / 2);

                                            console.log('Centering signature with offsets:', {
                                                offsetX,
                                                offsetY
                                            });

                                            // Draw the signature portion at the calculated position (centered)
                                            croppedCtx.drawImage(
                                                canvas,
                                                left, top, signatureWidth, signatureHeight,
                                                offsetX, offsetY, signatureWidth, signatureHeight
                                            );

                                            console.log('Cropped signature from', {
                                                    left,
                                                    top,
                                                    right,
                                                    bottom
                                                },
                                                'to size', {
                                                    width: finalWidth,
                                                    height: finalHeight
                                                });

                                            // Return the data URL of the cropped signature
                                            return croppedCanvas.toDataURL('image/png');
                                        };

                                        // Monitor for signature save actions
                                        Livewire.on('signature-modal-opened', () => {
                                            console.log('Signature modal opened, setting up auto-crop');

                                            // Set up the cropping handler after a short delay to ensure the UI is ready
                                            setTimeout(() => {
                                                // Find the canvas and the save button
                                                const canvas = findSignatureCanvas();
                                                console.log('Found signature canvas:', canvas);

                                                const saveButton = document.querySelector('[id$="save-signature-btn"]') ||
                                                    document.querySelector('.mary-modal button[wire\\:click*="saveSignature"]');

                                                console.log('Found save button:', saveButton);

                                                if (canvas && saveButton) {
                                                    // Remove any existing handlers to avoid duplicates
                                                    saveButton.removeEventListener('click', cropHandler);

                                                    // Define the crop handler
                                                    function cropHandler(e) {
                                                        console.log('Save button clicked, cropping signature...');

                                                        // Generate the cropped version
                                                        const croppedSignature = autoCropSignature(canvas);

                                                        // If we got a cropped version, update the temporary signature
                                                        if (croppedSignature) {
                                                            console.log('Signature cropped successfully');
                                                            // We're using Livewire, so dispatch the event
                                                            @this.set('tempSignature', croppedSignature);
                                                        } else {
                                                            console.log('No signature found to crop');
                                                        }
                                                    }

                                                    // Add click handler to the save button
                                                    saveButton.addEventListener('click', cropHandler);
                                                    console.log('Added crop handler to save button');
                                                }
                                            }, 500);
                                        });
                                    });
                                </script>
                                <script>
                                    document.addEventListener('livewire:initialized', function() {
                                        // Find the canvas element inside our container
                                        const container = document.getElementById('signature-canvas-container');
                                        const canvasElement = container.querySelector('canvas');

                                        if (canvasElement) {
                                            // Variables to track signature state
                                            let isDrawing = false;
                                            let isMouseOverCanvas = false;

                                            // Add event listeners for mouse containment
                                            canvasElement.addEventListener('mouseenter', function() {
                                                isMouseOverCanvas = true;
                                            });

                                            canvasElement.addEventListener('mouseleave', function() {
                                                isMouseOverCanvas = false;
                                                if (isDrawing) {
                                                    // Force stop drawing if mouse leaves canvas while drawing
                                                    isDrawing = false;
                                                    document.dispatchEvent(new MouseEvent('mouseup'));
                                                }
                                            });

                                            // Modify the mousedown behavior
                                            canvasElement.addEventListener('mousedown', function(e) {
                                                if (isMouseOverCanvas) {
                                                    isDrawing = true;
                                                    // Prevent event from propagating to parent elements
                                                    e.stopPropagation();
                                                }
                                            }, true);

                                            // Add a global mouseup handler to stop drawing
                                            document.addEventListener('mouseup', function() {
                                                isDrawing = false;
                                            });

                                            // Handle global mousemove - only allow drawing within the canvas
                                            document.addEventListener('mousemove', function(e) {
                                                if (isDrawing && !isMouseOverCanvas) {
                                                    // If drawing but mouse is outside canvas, stop drawing
                                                    isDrawing = false;
                                                    document.dispatchEvent(new MouseEvent('mouseup'));
                                                }
                                            });

                                            // Prevent text selection on the page while drawing
                                            canvasElement.addEventListener('selectstart', function(e) {
                                                e.preventDefault();
                                                return false;
                                            });

                                            // Add touch event handling for mobile
                                            canvasElement.addEventListener('touchstart', function(e) {
                                                isDrawing = true;
                                                isMouseOverCanvas = true;
                                                // Prevent scrolling while drawing
                                                e.preventDefault();
                                            }, {
                                                passive: false
                                            });

                                            canvasElement.addEventListener('touchend', function() {
                                                isDrawing = false;
                                                isMouseOverCanvas = false;
                                            });

                                            canvasElement.addEventListener('touchcancel', function() {
                                                isDrawing = false;
                                                isMouseOverCanvas = false;
                                            });

                                            // Style adjustments for better user experience
                                            canvasElement.style.touchAction = 'none'; // Disable browser touch actions
                                        }

                                        // Handle signature modal events
                                        Livewire.on('signature-modal-opened', () => {
                                            // Reset state when modal opens
                                            if (canvasElement) {
                                                // Apply additional styling or initialization if needed
                                            }
                                        });
                                    });
                                </script>

                                <!-- Optional visual indicator for signature area -->
                                <div class="mt-1 text-xs text-center text-gray-500">
                                    Drawing is contained within the signature area only
                                </div>
                            </div>
                        @endif

                        <!-- Topaz Signature Pad Integration -->
                        @if ($signatureMethod === 'topaz')
                            <div class="relative p-2 mb-4 bg-white border rounded-lg h-[550px]">
                                <div id="topaz-signature-container" class="flex flex-col w-full h-full">
                                    <!-- Canvas for signature display -->
                                    <canvas id="topaz-canvas" width="500" height="550"
                                        class="w-full h-full border rounded"></canvas>

                                    <!-- Status display -->
                                    <div id="sigweb-status" class="mt-2 text-xs text-gray-600"></div>

                                    <!-- Loading/placeholder overlay -->
                                    <div id="topaz-placeholder"
                                        class="absolute inset-0 z-10 flex items-center justify-center bg-white bg-opacity-80">
                                        <div class="text-center text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <p>Initializing Topaz Signature Pad...</p>
                                            <p class="mt-1 text-sm">Please connect your device</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between mt-2">
                                    <div>
                                        <x-mary-button type="button" id="topaz-clear"
                                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                            icon="o-trash">
                                            Clear
                                        </x-mary-button>
                                    </div>
                                    <div>
                                        <x-mary-button type="button" id="topaz-start"
                                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                            icon="o-play">
                                            Start Signing
                                        </x-mary-button>
                                        <x-mary-button type="button" id="topaz-accept"
                                            class="tagged-color btn-primary" size="sm" icon="o-check">
                                            Capture
                                        </x-mary-button>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('livewire:initialized', function() {
                                    // Variables and elements
                                    let statusDiv = document.getElementById('sigweb-status');
                                    let placeholderDiv = document.getElementById('topaz-placeholder');
                                    let topazCanvas = document.getElementById('topaz-canvas');
                                    let topazClearBtn = document.getElementById('topaz-clear');
                                    let topazAcceptBtn = document.getElementById('topaz-accept');
                                    let topazStartBtn = document.getElementById('topaz-start');
                                    let sigWebInitialized = false;
                                    let tmr;

                                    // Function to update status messages
                                    function updateStatus(message, isError = false) {
                                        if (statusDiv) {
                                            statusDiv.textContent = message;
                                            statusDiv.className = isError ?
                                                'mt-2 text-xs text-red-500' :
                                                'mt-2 text-xs text-gray-600';
                                        }
                                        console.log(isError ? 'Error: ' : 'Status: ', message);
                                    }

                                    // Check if SigWeb is installed
                                    function checkSigWebInstallation() {
                                        try {
                                            if (typeof IsSigWebInstalled === 'function' && IsSigWebInstalled()) {
                                                updateStatus('SigWeb service detected');
                                                return true;
                                            } else {
                                                updateStatus(
                                                    'SigWeb service not detected. Please install it from www.topazsystems.com/software/sigweb.exe',
                                                    true);
                                                return false;
                                            }
                                        } catch (e) {
                                            updateStatus('Error checking SigWeb installation: ' + e.message, true);
                                            return false;
                                        }
                                    }

                                    // Initialize the Topaz signature pad
                                    function initializeTopazPad() {
                                        try {
                                            if (placeholderDiv) placeholderDiv.style.display = 'flex';
                                            updateStatus('Connecting to signature pad...');

                                            if (!checkSigWebInstallation()) {
                                                if (placeholderDiv) placeholderDiv.style.display = 'none';
                                                return;
                                            }

                                            // Initialize canvas context
                                            const ctx = topazCanvas.getContext('2d');

                                            // Set up basic SigWeb properties
                                            SetDisplayXSize(500);
                                            SetDisplayYSize(100);
                                            SetTabletState(0);
                                            SetJustifyMode(0);
                                            ClearTablet();

                                            sigWebInitialized = true;
                                            if (placeholderDiv) placeholderDiv.style.display = 'none';
                                            updateStatus('Signature pad ready. Click "Start Signing" to begin.');
                                        } catch (error) {
                                            if (placeholderDiv) placeholderDiv.style.display = 'none';
                                            updateStatus('Failed to initialize signature pad: ' + error.message, true);
                                            console.error('Error initializing Topaz pad:', error);
                                        }
                                    }

                                    // Function to start signature capture
                                    function startSigning() {
                                        try {
                                            if (!sigWebInitialized) {
                                                updateStatus('Signature pad not initialized. Please try again.', true);
                                                return;
                                            }

                                            // Get model number to verify connection
                                            const modelNum = TabletModelNumber();
                                            if (modelNum > 0) {
                                                updateStatus('Starting signature capture. Please sign now...');

                                                const ctx = topazCanvas.getContext('2d');
                                                ClearTablet();

                                                // Start the tablet state
                                                tmr = SetTabletState(1, ctx, 50);
                                            } else {
                                                updateStatus('Tablet not detected. Please check connection.', true);
                                            }
                                        } catch (error) {
                                            updateStatus('Error starting signature capture: ' + error.message, true);
                                            console.error('Error starting signing:', error);
                                        }
                                    }

                                    // Function to clear the signature
                                    function clearSignature() {
                                        try {
                                            ClearTablet();
                                            updateStatus('Signature cleared. Ready for new signature.');
                                            @this.set('tempSignature', null);
                                        } catch (error) {
                                            updateStatus('Error clearing signature: ' + error.message, true);
                                            console.error('Error clearing signature:', error);
                                        }
                                    }

                                    // Function to capture the signature
                                    function captureSignature() {
                                        try {
                                            if (NumberOfTabletPoints() === 0) {
                                                updateStatus('No signature detected. Please sign before capturing.', true);
                                                return;
                                            }

                                            // Set up compression and formatting for signature data
                                            SetSigCompressionMode(1); // Lossless compression

                                            // Get the signature as a base64 image
                                            GetSigImageB64(function(sigImageData) {
                                                if (sigImageData) {
                                                    // Format as data URL
                                                    const base64Image = 'data:image/png;base64,' + sigImageData;

                                                    // Send to Livewire component
                                                    @this.set('tempSignature', base64Image);

                                                    updateStatus('Signature captured successfully!');
                                                    SetTabletState(0, tmr);
                                                } else {
                                                    updateStatus('Failed to capture signature image.', true);
                                                }
                                            });
                                        } catch (error) {
                                            updateStatus('Error capturing signature: ' + error.message, true);
                                            console.error('Error capturing signature:', error);
                                        }
                                    }

                                    // Add event listeners for buttons
                                    if (topazClearBtn) {
                                        topazClearBtn.addEventListener('click', clearSignature);
                                    }

                                    if (topazStartBtn) {
                                        topazStartBtn.addEventListener('click', startSigning);
                                    }

                                    if (topazAcceptBtn) {
                                        topazAcceptBtn.addEventListener('click', captureSignature);
                                    }

                                    // Listen for modal events
                                    Livewire.on('signature-modal-opened', () => {
                                        if (@this.signatureMethod === 'topaz') {
                                            setTimeout(initializeTopazPad, 100);
                                        }
                                    });

                                    // Handle changes to signature method
                                    @this.watch('signatureMethod', value => {
                                        if (value === 'topaz') {
                                            setTimeout(initializeTopazPad, 100);
                                        } else {
                                            // Stop the tablet if user switches away from Topaz method
                                            try {
                                                if (sigWebInitialized) {
                                                    SetTabletState(0, tmr);
                                                }
                                            } catch (e) {
                                                console.log('Error stopping tablet:', e);
                                            }
                                        }
                                    });

                                    // Cleanup when modal is closed
                                    Livewire.on('signature-modal-closed', () => {
                                        try {
                                            if (sigWebInitialized) {
                                                SetTabletState(0, tmr);
                                                clearInterval(tmr);
                                            }
                                        } catch (e) {
                                            console.log('Error cleaning up tablet:', e);
                                        }
                                    });

                                    // Initialize if modal is already open
                                    if (@this.showSignatureModal && @this.signatureMethod === 'topaz') {
                                        setTimeout(initializeTopazPad, 100);
                                    }
                                });
                            </script>
                        @endif

                        <!-- Modal Footer with Actions -->
                        <x-slot:actions>
                            <div class="flex justify-between mt-10 gap-x-4">
                                <div class="flex gap-x-2">
                                    <x-mary-button wire:click="$set('showSignatureModal', false)"
                                        class="tagged-color btn-secondary btn-outline btn-secline">
                                        Cancel
                                    </x-mary-button>
                                    <x-mary-button wire:click="saveSignature" class="tagged-color btn-primary"
                                        icon="o-check">
                                        Save Signature
                                    </x-mary-button>
                                </div>
                            </div>
                        </x-slot:actions>
                    </x-mary-modal>
                </div>
            </div>

            <div class="flex justify-end mt-8 space-x-3">
                <x-mary-button label="Cancel" class="tagged-color btn-secondary btn-outline btn-secline"
                    link="{{ route('residents.index') }}" />
                <x-mary-button type="button" label="Reset Form" wire:click="resetForm"
                    class="tagged-color btn-warning" />
                <x-mary-button type="submit" label="{{ $isEdit ? 'Update Resident' : 'Register Resident' }}"
                    wire:click.prevent="save" wire:loading.attr="disabled" icon="o-paper-airplane">
                    <span wire:loading.remove
                        wire:target="save">{{ $isEdit ? 'Update Resident' : 'Register Resident' }}</span>
                    <span wire:loading wire:target="save">Processing...</span>
                </x-mary-button>
            </div>

            <!-- Debug Information (remove in production) -->
            @if (app()->environment('local'))
                <div class="p-4 mt-8 overflow-auto text-xs rounded-lg bg-gray-50 max-h-40">
                    <h4 class="mb-2 font-medium">Debug Info:</h4>
                    <p>Form State: {{ $errors->any() ? 'Has errors' : 'No errors' }}</p>
                    @if ($errors->any())
                        <ul class="pl-5 text-red-600 list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p>Modals: Signature: {{ $showSignatureModal ? 'Open' : 'Closed' }}, Webcam:
                        {{ $showWebcamModal ? 'Open' : 'Closed' }}</p>
                    <p>Has Signature: {{ $signature ? 'Yes' : 'No' }}</p>
                    <p>Signature Method: {{ $signatureMethod }}</p>
                    <p>Has Photo: {{ $photo ? 'Yes (File)' : ($capturedPhoto ? 'Yes (Webcam)' : 'No') }}</p>
                </div>
            @endif
        </form>
    </x-mary-card>

    @push('scripts')
        <script>
            // This script ensures the Topaz signature pad resources are properly cleaned up
            // when navigating away from the page or refreshing
            window.addEventListener('beforeunload', function(e) {
                try {
                    // If SigWeb is available, clean up resources
                    if (typeof SetTabletState === 'function') {
                        SetTabletState(0);
                    }

                    // Clear any timers that might be running
                    const activeTimers = window.sessionStorage.getItem('active-signature-timers');
                    if (activeTimers) {
                        const timerIds = JSON.parse(activeTimers);
                        timerIds.forEach(id => clearInterval(id));
                        window.sessionStorage.removeItem('active-signature-timers');
                    }
                } catch (err) {
                    console.log('Error during cleanup:', err);
                }
            });

            // Helper function to check if SigWeb is installed
            function checkSigWebAvailability() {
                try {
                    return typeof IsSigWebInstalled === 'function' && IsSigWebInstalled();
                } catch (e) {
                    console.log('Error checking SigWeb availability:', e);
                    return false;
                }
            }

            // Additional event listener for when signature component is initialized
            document.addEventListener('livewire:initialized', function() {
                // Update the UI based on SigWeb availability
                const sigWebAvailable = checkSigWebAvailability();

                // Store information about availability for later use
                window.sessionStorage.setItem('sigweb-available', sigWebAvailable ? 'true' : 'false');

                console.log('SigWeb service availability:', sigWebAvailable);
            });
        </script>
    @endpush
</div>
