<div>
    <div class="overflow-hidden border rounded-lg shadow-sm bg-base">
        <div class="px-4 py-3 border-b bg-base-50">
            <h3 class="text-lg font-medium">{{ $title }}</h3>
            <p class="text-sm text-gray-600">{{ $description }}</p>
        </div>

        <div class="p-4">
            <!-- Scanner Controls -->
            <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                @if ($showCamera)
                    <x-mary-button wire:click="toggleScanner" icon="o-{{ $isScanning ? 'x-circle' : 'camera' }}"
                        class="{{ $isScanning ? 'btn-secondary btn-outline btn-secline' : 'btn-primary' }}">
                        {{ $isScanning ? 'Close Scanner' : 'Open QR Scanner' }}
                    </x-mary-button>
                @endif

                @if ($showRfidInput)
                    <div class="flex flex-1 gap-2">
                        <x-mary-input wire:model="rfidInput" placeholder="Enter RFID number..." class="flex-1"
                            wire:keydown.enter="processRfid" />
                        <x-mary-button wire:click="processRfid" icon="o-check-circle" class="tagged-color btn-primary">
                            Process
                        </x-mary-button>
                    </div>
                @endif
            </div>

            <!-- QR Scanner -->
            <div x-data="{
                scanner: null,
                initScanner() {
                    // Make sure to stop any existing scanner first
                    if (this.scanner) {
                        this.scanner.stop().catch(e => console.error('Error stopping scanner:', e));
                    }
            
                    this.scanner = new Html5Qrcode('qr-reader');
                    const config = {
                        fps: 10,
                        qrbox: {
                            width: 250,
                            height: 250
                        },
                        aspectRatio: 1.7777778,
                    };
            
                    // Try to find the rear camera on mobile devices
                    Html5Qrcode.getCameras().then(cameras => {
                        console.log('Available cameras:', cameras);
                        let cameraId;
            
                        // Common keywords for rear cameras on mobile devices
                        const rearCameraKeywords = [
                            'back', 'rear', 'environment',
                            'camera 0', 'camera 1', // Many devices use these naming conventions
                            '0', // Often the first camera (index 0) is the rear one
                            'wide', 'ultra', // Some newer phones use these terms for rear cameras
                            'BRIO' // Still keep the original BRIO preference for desktop
                        ];
            
                        // First look for cameras matching our keywords
                        const rearCamera = cameras.find(camera => {
                            if (!camera.label) return false;
                            const label = camera.label.toLowerCase();
                            return rearCameraKeywords.some(keyword =>
                                label.includes(keyword.toLowerCase()));
                        });
            
                        if (rearCamera) {
                            console.log('Found rear camera:', rearCamera);
                            cameraId = rearCamera.id;
                        }
                        // If no camera matched our keywords but we have multiple cameras
                        // Try to use the first camera (usually rear on mobile)
                        else if (cameras.length > 0) {
                            console.log('Using first available camera:', cameras[0]);
                            cameraId = cameras[0].id;
                        }
                        // Last resort - explicitly request environment-facing camera
                        else {
                            console.log('No cameras found, using environment facing mode');
                            cameraId = { facingMode: { exact: 'environment' } };
                        }
            
                        // Start scanner with selected camera
                        this.scanner.start(
                            cameraId,
                            config,
                            (decodedText) => {
                                console.log('QR code detected:', decodedText);
                                const processedCode = decodedText;
                                console.log('Processing code:', processedCode);
                                @this.call('processQrCode', processedCode);
                            },
                            (errorMessage) => {
                                console.warn('QR Code error:', errorMessage);
                            }
                        ).catch(err => {
                            console.error('Failed to start scanner:', err);
            
                            // Fallback to environment facing camera if specific camera fails
                            if (typeof cameraId !== 'object') {
                                console.log('Trying fallback to environment facing camera');
                                this.scanner.start({ facingMode: 'environment' },
                                    config,
                                    (decodedText) => {
                                        console.log('QR code detected:', decodedText);
                                        const processedCode = decodedText;
                                        console.log('Processing code:', processedCode);
                                        @this.call('processQrCode', processedCode);
                                    },
                                    (errorMessage) => {
                                        console.warn('QR Code error:', errorMessage);
                                    }
                                ).catch(e => {
                                    console.error('Fallback camera also failed, trying any camera:', e);
                                    // Last resort - try any camera
                                    if (cameras.length > 0) {
                                        this.tryAllCameras(cameras, 0, config);
                                    }
                                });
                            }
                        });
                    }).catch(err => {
                        console.error('Error getting cameras:', err);
            
                        // Try to start with environment facing camera as fallback
                        this.scanner.start({ facingMode: 'environment' },
                            config,
                            (decodedText) => {
                                console.log('QR code detected:', decodedText);
                                const processedCode = decodedText;
                                console.log('Processing code:', processedCode);
                                @this.call('processQrCode', processedCode);
                            },
                            (errorMessage) => {
                                console.warn('QR Code error:', errorMessage);
                            }
                        ).catch(e => console.error('Failed to start scanner with fallback:', e));
                    });
                },
            
                // Helper method to try all cameras sequentially if other methods fail
                tryAllCameras(cameras, index, config) {
                    if (index >= cameras.length) {
                        console.error('Tried all cameras, none worked');
                        return;
                    }
            
                    console.log(`Trying camera ${index}: ${cameras[index].label}`);
                    this.scanner.start(
                        cameras[index].id,
                        config,
                        (decodedText) => {
                            console.log('QR code detected:', decodedText);
                            const processedCode = decodedText;
                            console.log('Processing code:', processedCode);
                            @this.call('processQrCode', processedCode);
                        },
                        (errorMessage) => {
                            console.warn('QR Code error:', errorMessage);
                        }
                    ).catch(e => {
                        console.error(`Failed with camera ${index}:`, e);
                        this.tryAllCameras(cameras, index + 1, config);
                    });
                },
            
                stopScanner() {
                    if (this.scanner) {
                        this.scanner.stop().catch(e => console.error('Error stopping scanner:', e));
                        this.scanner = null;
                        console.log('Scanner stopped');
                    }
                }
            }" x-init="$watch('$wire.isScanning', value => {
                console.log('Scanning state changed:', value);
                if (value) {
                    // Small delay to ensure DOM is ready
                    setTimeout(() => initScanner(), 100);
                } else {
                    stopScanner();
                }
            })">

                @if ($showCamera && $isScanning)
                    <div class="mb-4">
                        <div class="relative max-w-md mx-auto overflow-hidden border rounded-lg aspect-video">
                            <div id="qr-reader" class="w-full h-full"></div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Results Display -->
            @if ($resultFound)
                <div class="p-4 mb-4 border border-green-200 rounded-lg bg-green-50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Found {{ ucfirst($resultType) }}</h3>
                            <div class="mt-1 text-sm text-green-700">
                                <p>{{ $resultMessage }}</p>

                                @if ($resultType === 'resident' && $resultObject)
                                    <div class="px-3 py-2 mt-2 rounded bg-base">
                                        <p><span class="font-medium">Name:</span> {{ $resultObject->full_name }}</p>
                                        <p><span class="font-medium">ID:</span> {{ $resultObject->resident_id }}</p>
                                        @if (isset($resultObject->birth_date))
                                            <p><span class="font-medium">Birth Date:</span>
                                                {{ $resultObject->birth_date->format('M d, Y') }}</p>
                                        @endif
                                    </div>
                                @elseif($resultType === 'household' && $resultObject)
                                    <div class="px-3 py-2 mt-2 rounded bg-base">
                                        <p><span class="font-medium">Household ID:</span>
                                            {{ $resultObject->household_id }}</p>
                                        <p><span class="font-medium">Address:</span> {{ $resultObject->address }}</p>
                                        @if (isset($resultObject->member_count))
                                            <p><span class="font-medium">Members:</span>
                                                {{ $resultObject->member_count }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4 space-x-3">
                        <x-mary-button wire:click="clearResult"
                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm">
                            Clear
                        </x-mary-button>

                        <x-mary-button wire:click="processResult" class="tagged-color btn-primary" size="sm">
                            View Details
                        </x-mary-button>
                    </div>
                </div>
            @elseif($scanResult && !$resultFound)
                <div class="p-4 mb-4 border border-red-200 rounded-lg bg-red-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Not Found</h3>
                            <div class="mt-1 text-sm text-red-700">
                                <p>{{ $resultMessage }}</p>
                                <p class="mt-1">Scanned value: {{ $scanResult }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <x-mary-button wire:click="clearResult"
                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm">
                            Clear
                        </x-mary-button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Make sure HTML5QrCode library is loaded --}}
    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
        <script>
            // Set up listener for qr-scanner-close event
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('qr-scanner-close', () => {
                    // This will be caught by Alpine's x-watch
                    console.log('Received qr-scanner-close event');
                });
            });
        </script>
    @endpush
</div
