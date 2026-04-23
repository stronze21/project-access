<div>
    <x-mary-card title="Update Resident Signature">
        <x-slot:menu>
            <x-mary-button link="{{ route('residents.show', $residentId) }}" label="View Resident"
                class="tagged-color btn-primary" size="sm" />
            <x-mary-button link="{{ route('residents.index') }}" label="All Residents"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
        </x-slot:menu>

        @if ($signatureUpdated)
            <div class="p-4 mb-6 text-green-800 rounded-lg bg-green-50">
                <div class="flex items-center mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <h3 class="text-lg font-semibold">Signature Updated Successfully</h3>
                </div>
                <p>The resident's signature has been updated in the system.</p>
                <div class="mt-4">
                    <x-mary-button link="{{ route('residents.show', $residentId) }}" label="Back to Resident Details"
                        class="tagged-color btn-primary" />
                </div>
            </div>
        @else
            <div class="p-2 mb-6">
                <!-- Resident Info Summary -->
                <div class="p-4 mb-6 border rounded-lg bg-base-100">
                    @if ($resident)
                        <div class="flex flex-col items-center gap-4 md:flex-row">
                            <div class="flex-shrink-0">
                                @if ($resident->photo_path)
                                    <img src="{{ Storage::url($resident->photo_path) }}"
                                        class="object-cover w-20 h-20 border rounded-full"
                                        alt="{{ $resident->first_name }}'s photo">
                                @else
                                    <div class="flex items-center justify-center w-20 h-20 bg-gray-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow text-center md:text-left">
                                <h2 class="text-xl font-semibold">{{ $resident->first_name }}
                                    {{ $resident->middle_name ? substr($resident->middle_name, 0, 1) . '.' : '' }}
                                    {{ $resident->last_name }} {{ $resident->suffix }}</h2>
                                <p class="text-gray-600">Resident ID: {{ $resident->resident_id }}</p>
                                <p class="text-gray-600">
                                    <span class="mr-3">Age: {{ $resident->getAge() }}</span>
                                    <span class="mr-3">Gender: {{ ucfirst($resident->gender) }}</span>
                                </p>
                            </div>
                        </div>
                    @else
                        <p class="text-center text-gray-600">Resident information not available</p>
                    @endif
                </div>

                <!-- Signature Update Section -->
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Update Signature</h3>

                    <div class="flex flex-col gap-6 mb-6 md:flex-row">
                        <!-- Current Signature -->
                        <div class="flex-1">
                            <h4 class="mb-2 font-medium">Current Signature</h4>
                            <div class="flex items-center justify-center h-40 p-4 bg-white border rounded-lg">
                                @if ($signature)
                                    <img src="{{ $signature }}" alt="Current Signature"
                                        class="object-contain max-w-full max-h-full">
                                @else
                                    <div class="text-center text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="mt-2">No signature on file</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Capture Options -->
                    <div class="mb-6">
                        <h4 class="mb-4 font-medium">Capture New Signature</h4>

                        <div class="flex flex-col gap-4">
                            <!-- XP-Pen Tablet Capture -->
                            <div class="p-4 border rounded-lg bg-base-50">
                                <div class="flex items-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2 text-primary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <h5 class="font-medium">XP-Pen G430S Drawing Tablet</h5>
                                </div>

                                <p class="mb-4 text-gray-600">Use the connected XP-Pen G430S drawing tablet to capture a
                                    natural signature.</p>

                                <x-mary-button wire:click="$set('showSignatureModal', true)"
                                    class="tagged-color btn-primary" icon="o-pencil-square">
                                    Capture Signature with Tablet
                                </x-mary-button>
                            </div>

                            <!-- Alternative: File Upload -->
                            <div class="p-4 border rounded-lg bg-base-50">
                                <div class="flex items-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <h5 class="font-medium">Upload Signature Image</h5>
                                </div>

                                <p class="mb-4 text-gray-600">Upload a pre-scanned signature image file.</p>

                                <x-mary-file wire:model="signatureFile" accept="image/*"
                                    hint="Upload a signature image (PNG, JPG)" />
                            </div>
                        </div>
                    </div>

                    <!-- Signature Capture Modal -->
                    <x-mary-modal wire:model.live="showSignatureModal" title="Capture Signature with XP-Pen Tablet"
                        max-width="md" persistent>
                        <!-- Add this CSS to hide watermark text -->
                        <style>
                            /* Hide watermark text in signature pad */
                            .mary-signature canvas+div,
                            .mary-signature canvas+span,
                            .mary-signature .signature-pad-container .signature-pad-body>div:not(canvas),
                            .mary-signature .m-signature-pad .m-signature-pad--body>div:not(canvas),
                            .mary-signature [data-signature-hint] {
                                display: none !important;
                                opacity: 0 !important;
                                visibility: hidden !important;
                            }

                            /* Keep the canvas visible */
                            .mary-signature canvas {
                                display: block !important;
                                visibility: visible !important;
                            }

                            /* Set proper container dimensions */
                            .mary-signature {
                                min-height: 250px;
                            }

                            /* Disable hover effects for XP-Pen */
                            #xppen-canvas {
                                cursor: default !important;
                            }

                            /* Only show cursor when drawing */
                            #xppen-canvas.drawing {
                                cursor: crosshair !important;
                            }

                            /* XP-Pen specific styles */
                            #xppen-signature-container {
                                position: relative;
                                width: 100%;
                                height: 250px;
                                border: 1px solid #e5e7eb;
                                border-radius: 0.375rem;
                                background-color: white;
                                overflow: hidden;
                            }

                            #xppen-canvas {
                                width: 100%;
                                height: 100%;
                                touch-action: none;
                            }

                            .pen-size-selector {
                                display: flex;
                                gap: 10px;
                                margin-bottom: 10px;
                            }

                            .pen-size-option {
                                width: 30px;
                                height: 30px;
                                border-radius: 50%;
                                border: 1px solid #e5e7eb;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                cursor: pointer;
                            }

                            .pen-size-option.active {
                                border-color: #3b82f6;
                                background-color: #eff6ff;
                            }

                            .pen-size-dot {
                                border-radius: 50%;
                                background-color: black;
                            }
                        </style>

                        <!-- XP-Pen Drawing Area -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-700">Sign using the XP-Pen G430S tablet</h4>
                                <div>
                                    <x-mary-button type="button" id="clear-xppen"
                                        class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                        icon="o-trash">
                                        Clear
                                    </x-mary-button>
                                </div>
                            </div>

                            <!-- Pen Size Selector -->
                            <div class="pen-size-selector">
                                <div class="pen-size-option active" data-size="1">
                                    <div class="pen-size-dot" style="width: 1px; height: 1px;"></div>
                                </div>
                                <div class="pen-size-option" data-size="2">
                                    <div class="pen-size-dot" style="width: 2px; height: 2px;"></div>
                                </div>
                                <div class="pen-size-option" data-size="3">
                                    <div class="pen-size-dot" style="width: 3px; height: 3px;"></div>
                                </div>
                                <div class="pen-size-option" data-size="4">
                                    <div class="pen-size-dot" style="width: 4px; height: 4px;"></div>
                                </div>
                            </div>

                            <!-- Canvas Container -->
                            <div id="xppen-signature-container">
                                <canvas id="xppen-canvas" width="1000" height="500"></canvas>
                            </div>

                            <div class="mt-2 text-sm text-center text-gray-500">
                                Use your XP-Pen G430S stylus to sign above
                            </div>
                        </div>

                        <script>
                            document.addEventListener('livewire:initialized', function() {
                                // Initialize variables
                                let canvas = document.getElementById('xppen-canvas');
                                let clearBtn = document.getElementById('clear-xppen');
                                let penSizeOptions = document.querySelectorAll('.pen-size-option');
                                let currentPenSize = 1; // Default pen size
                                let isDrawing = false;
                                let ctx;
                                let lastX = 0;
                                let lastY = 0;

                                // Set up the canvas
                                function initializeCanvas() {
                                    if (!canvas) return;

                                    ctx = canvas.getContext('2d');

                                    // Set canvas size to match container size for better resolution
                                    resizeCanvas();

                                    // Clear the canvas
                                    clearCanvas();

                                    // Set up default drawing style
                                    ctx.lineJoin = 'round';
                                    ctx.lineCap = 'round';
                                    ctx.strokeStyle = '#000000'; // Black color
                                    ctx.lineWidth = currentPenSize;

                                    // Set canvas attributes to disable hover
                                    canvas.setAttribute('touch-action', 'none');

                                    // Disable hover effects for XP-Pen specifically
                                    if (navigator.userAgent.includes('Windows')) {
                                        // Windows-specific handling for WinTab drivers
                                        canvas.style.pointerEvents = 'all';
                                    }

                                    // Add event listeners specific to the tablet and stylus
                                    addEventListeners();
                                }

                                // Resize canvas to match container
                                function resizeCanvas() {
                                    const container = canvas.parentElement;
                                    canvas.width = container.clientWidth;
                                    canvas.height = container.clientHeight;
                                }

                                // Clear the canvas
                                function clearCanvas() {
                                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                                }

                                // Start drawing
                                function startDrawing(e) {
                                    // Only respond to pen or mouse primary button (not hovering)
                                    if (e.pointerType === 'pen' && e.pressure === 0) {
                                        return; // Ignore hovering pen events
                                    }

                                    if (e.buttons !== undefined && e.buttons !== 1 && e.pointerType !== 'touch') {
                                        return; // Only proceed with primary button for mouse
                                    }

                                    isDrawing = true;
                                    canvas.classList.add('drawing'); // Add drawing class for cursor change

                                    // Get the correct position
                                    const pos = getPosition(e);
                                    lastX = pos.x;
                                    lastY = pos.y;

                                    // Draw a single point
                                    ctx.beginPath();
                                    ctx.arc(lastX, lastY, ctx.lineWidth / 2, 0, Math.PI * 2);
                                    ctx.fill();

                                    // Prevent default behavior
                                    if (e.preventDefault) {
                                        e.preventDefault();
                                    }
                                }

                                // Draw
                                function draw(e) {
                                    if (!isDrawing) return;

                                    // Skip pen events with zero pressure (hovering)
                                    if (e.pointerType === 'pen' && e.pressure === 0) {
                                        return;
                                    }

                                    // Get the current position
                                    const pos = getPosition(e);

                                    // Draw the line
                                    ctx.beginPath();
                                    ctx.moveTo(lastX, lastY);
                                    ctx.lineTo(pos.x, pos.y);
                                    ctx.stroke();

                                    // Update last position
                                    lastX = pos.x;
                                    lastY = pos.y;

                                    // Prevent default behavior
                                    if (e.preventDefault) {
                                        e.preventDefault();
                                    }
                                }

                                // Stop drawing
                                function stopDrawing() {
                                    isDrawing = false;
                                    canvas.classList.remove('drawing'); // Remove drawing class
                                }

                                // Get the pointer position
                                function getPosition(e) {
                                    let x, y;

                                    // Handle both mouse and touch events
                                    if (e.type.includes('touch')) {
                                        const rect = canvas.getBoundingClientRect();
                                        x = e.touches[0].clientX - rect.left;
                                        y = e.touches[0].clientY - rect.top;
                                    } else {
                                        const rect = canvas.getBoundingClientRect();
                                        x = e.clientX - rect.left;
                                        y = e.clientY - rect.top;
                                    }

                                    return {
                                        x,
                                        y
                                    };
                                }

                                // Add event listeners
                                function addEventListeners() {
                                    // Mouse events
                                    canvas.addEventListener('mousedown', startDrawing);
                                    canvas.addEventListener('mousemove', draw);
                                    canvas.addEventListener('mouseup', stopDrawing);
                                    canvas.addEventListener('mouseout', stopDrawing);

                                    // Touch events for tablet
                                    canvas.addEventListener('touchstart', startDrawing);
                                    canvas.addEventListener('touchmove', draw);
                                    canvas.addEventListener('touchend', stopDrawing);

                                    // Pointer events - better for stylus
                                    canvas.addEventListener('pointerdown', startDrawing);
                                    canvas.addEventListener('pointermove', function(e) {
                                        // Only process pointermove if already drawing
                                        // This prevents hover effects
                                        if (isDrawing) {
                                            draw(e);
                                        }
                                    });
                                    canvas.addEventListener('pointerup', stopDrawing);
                                    canvas.addEventListener('pointerout', stopDrawing);

                                    // Disable pointer events when not drawing
                                    canvas.style.touchAction = 'none'; // Disable browser touch actions

                                    // Disable hover effects by explicitly setting pointer-events CSS
                                    // This forces the canvas to only react to direct contact
                                    canvas.addEventListener('pointerenter', function(e) {
                                        if (!isDrawing && e.pointerType === 'pen') {
                                            // Ignore hover events from the pen
                                            e.preventDefault();
                                        }
                                    });

                                    // Prevent scrolling while drawing on mobile
                                    canvas.addEventListener('touchstart', function(e) {
                                        e.preventDefault();
                                    }, {
                                        passive: false
                                    });

                                    canvas.addEventListener('touchmove', function(e) {
                                        e.preventDefault();
                                    }, {
                                        passive: false
                                    });

                                    // Clear button
                                    clearBtn.addEventListener('click', clearCanvas);

                                    // Pen size selectors
                                    penSizeOptions.forEach(option => {
                                        option.addEventListener('click', function() {
                                            // Remove active class from all options
                                            penSizeOptions.forEach(opt => opt.classList.remove('active'));

                                            // Add active class to clicked option
                                            this.classList.add('active');

                                            // Update pen size
                                            currentPenSize = parseInt(this.getAttribute('data-size'));
                                            ctx.lineWidth = currentPenSize;
                                        });
                                    });

                                    // Window resize event
                                    window.addEventListener('resize', resizeCanvas);
                                }

                                // Function to get signature as image data URL
                                function getSignatureImage() {
                                    return canvas.toDataURL('image/png');
                                }

                                // Initialize when modal opens
                                Livewire.on('signature-modal-opened', () => {
                                    setTimeout(initializeCanvas, 100);
                                });

                                // Clean up when modal closes
                                Livewire.on('signature-modal-closed', () => {
                                    // Remove event listeners if needed
                                });

                                // Enable save button to send signature data to Livewire
                                document.getElementById('save-signature-btn').addEventListener('click', function() {
                                    const signatureData = getSignatureImage();
                                    @this.set('tempSignature', signatureData);
                                    @this.saveSignature();
                                });

                                // Initialize if modal is already open
                                if (@this.showSignatureModal) {
                                    setTimeout(initializeCanvas, 100);
                                }
                            });
                        </script>

                        <x-slot:actions>
                            <div class="flex justify-end space-x-3">
                                <x-mary-button wire:click="$set('showSignatureModal', false)"
                                    class="tagged-color btn-secondary btn-outline btn-secline">
                                    Cancel
                                </x-mary-button>
                                <x-mary-button id="save-signature-btn" class="tagged-color btn-primary"
                                    icon="o-check">
                                    Use This Signature
                                </x-mary-button>
                            </div>
                        </x-slot:actions>
                    </x-mary-modal>

                    <!-- Form Actions -->
                    <div class="flex justify-end mt-8 space-x-3">
                        <x-mary-button link="{{ route('residents.show', $residentId) }}"
                            class="tagged-color btn-secondary btn-outline btn-secline">
                            Cancel
                        </x-mary-button>
                        <x-mary-button wire:click="save" class="tagged-color btn-primary" icon="o-paper-airplane">
                            <span wire:loading.remove wire:target="save">Update Signature</span>
                            <span wire:loading wire:target="save">Processing...</span>
                        </x-mary-button>
                    </div>
                </div>
            </div>
        @endif
    </x-mary-card>
</div>
