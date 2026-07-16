<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-base-content/90 leading-tight">
                Quick Ticket (3 Steps)
            </h2>
            <a href="{{ route('complaints.create') }}"
               class="inline-flex w-fit items-center rounded-lg border border-base-300 px-3 py-1.5 text-xs font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-xs">
                Switch to Detailed Form
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 alert alert-error">
                    <p class="font-semibold">Please check your quick ticket details.</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-cyan-900 to-blue-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-base-100/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Simple Mode</p>
                    <h3 class="mt-2 text-2xl font-bold">Take a Photo, Add Brief Info, Submit</h3>
                    <p class="mt-2 max-w-3xl text-sm text-cyan-100/90">
                        Designed for fast citizen reporting when you do not want to fill the full complaint form.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
                <div class="mb-5 grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-lg px-2 py-2 font-semibold" data-step-indicator="0">1. Photo</div>
                    <div class="rounded-lg px-2 py-2 font-semibold" data-step-indicator="1">2. Details</div>
                    <div class="rounded-lg px-2 py-2 font-semibold" data-step-indicator="2">3. Submit</div>
                </div>

                <form id="quick-ticket-form" method="POST" action="{{ route('complaints.quick.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="space-y-4" data-step-panel="0">
                        <h4 class="text-base font-semibold text-base-content">Step 1: Take or Upload a Photo</h4>
                        <p class="text-sm text-base-content/70">Attach one clear photo of the issue. On mobile, camera capture is supported.</p>

                        <div class="rounded-xl border border-base-300 bg-base-200 p-4">
                            <p class="text-sm font-semibold text-base-content">Issue Photo</p>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <button type="button"
                                        id="open-camera-btn"
                                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                                    Open Camera
                                </button>
                                <button type="button"
                                        id="upload-photo-btn"
                                        class="inline-flex items-center justify-center rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                                    Upload Picture
                                </button>
                                <span id="photo-file-name" class="text-xs text-base-content/70">No photo selected</span>
                            </div>
                            <p id="camera-status" class="mt-2 text-xs text-base-content/70"></p>

                            <div id="camera-panel" class="mt-3 hidden rounded-lg border border-base-300 bg-black p-2">
                                <div id="camera-select-wrapper" class="mb-2 hidden rounded-md bg-base-100/90 p-2">
                                    <label for="camera-device-select" class="text-xs font-semibold text-base-content/80">Choose Camera</label>
                                    <select id="camera-device-select"
                                            class="mt-1 block w-full rounded-md border-base-300 bg-base-100 text-xs text-base-content select select-bordered">
                                    </select>
                                </div>
                                <video id="camera-video" autoplay playsinline class="h-56 w-full rounded-md object-cover sm:h-72"></video>
                                <canvas id="camera-canvas" class="hidden"></canvas>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button"
                                            id="capture-photo-btn"
                                            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 btn btn-success btn-xs">
                                        Capture Photo
                                    </button>
                                    <button type="button"
                                            id="close-camera-btn"
                                            class="inline-flex items-center justify-center rounded-lg border border-base-300 bg-base-100 px-3 py-1.5 text-xs font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-xs">
                                        Close Camera
                                    </button>
                                </div>
                            </div>

                            <input id="photo"
                                   name="photo"
                                   type="file"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="sr-only file-input file-input-bordered"
                                   required>
                            <p class="mt-1 text-xs text-base-content/60">Allowed: JPG, PNG, WEBP (max 20MB).</p>
                            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                        </div>

                        <div id="photo-preview-wrapper" class="hidden rounded-xl border border-base-300 p-2">
                            <img id="photo-preview" alt="Photo preview" class="h-56 w-full rounded-lg object-cover sm:h-72">
                        </div>
                    </div>

                    <div class="space-y-4 hidden" data-step-panel="1">
                        <h4 class="text-base font-semibold text-base-content">Step 2: Quick Details</h4>
                        <p class="text-sm text-base-content/70">Just the basics so we can route your ticket quickly.</p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="category_id" class="text-sm font-semibold text-base-content">Issue Category</label>
                                <select id="category_id" name="category_id" class="mt-1 block w-full rounded-lg border-base-300 text-sm select select-bordered" required>
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="visibility" class="text-sm font-semibold text-base-content">Public Visibility</label>
                                <select id="visibility" name="visibility" class="mt-1 block w-full rounded-lg border-base-300 text-sm select select-bordered" required>
                                    @foreach ($visibilityOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('visibility', \App\Models\Complaint::VISIBILITY_PUBLIC_ANONYMOUS) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <label for="issue_summary" class="text-sm font-semibold text-base-content">What is the issue? (short)</label>
                            <textarea id="issue_summary"
                                      name="issue_summary"
                                      rows="3"
                                      maxlength="280"
                                      class="mt-1 block w-full rounded-lg border-base-300 text-sm textarea textarea-bordered"
                                      placeholder="Example: Flooding near barangay hall every heavy rain."
                                      required>{{ old('issue_summary') }}</textarea>
                            <p class="mt-1 text-xs text-base-content/60">Max 280 characters.</p>
                            <x-input-error :messages="$errors->get('issue_summary')" class="mt-2" />
                        </div>

                        <div>
                            <label for="details" class="text-sm font-semibold text-base-content">Additional details (optional)</label>
                            <textarea id="details"
                                      name="details"
                                      rows="3"
                                      class="mt-1 block w-full rounded-lg border-base-300 text-sm textarea textarea-bordered"
                                      placeholder="Add landmarks, schedule, or important context.">{{ old('details') }}</textarea>
                            <x-input-error :messages="$errors->get('details')" class="mt-2" />
                        </div>
                    </div>

                    <div class="space-y-4 hidden" data-step-panel="2">
                        <h4 class="text-base font-semibold text-base-content">Step 3: Review and Submit</h4>
                        <p class="text-sm text-base-content/70">Check your quick ticket before sending.</p>

                        <div class="rounded-xl border border-base-300 bg-base-200 p-4 text-sm">
                            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Category</dt>
                                    <dd id="review-category" class="mt-0.5 font-semibold text-base-content">-</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Visibility</dt>
                                    <dd id="review-visibility" class="mt-0.5 font-semibold text-base-content">-</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Issue Summary</dt>
                                    <dd id="review-summary" class="mt-0.5 text-base-content">-</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Additional Details</dt>
                                    <dd id="review-details" class="mt-0.5 text-base-content">Not provided</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-base-300 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('complaints.create') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                            Use Detailed Form
                        </a>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button type="button"
                                    id="quick-prev-btn"
                                    class="hidden inline-flex items-center justify-center rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                                Back
                            </button>
                            <button type="button"
                                    id="quick-next-btn"
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                                Next
                            </button>
                            <button type="submit"
                                    id="quick-submit-btn"
                                    class="hidden inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 btn btn-success btn-sm">
                                Submit Quick Ticket
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('quick-ticket-form');
            if (!form) {
                return;
            }

            const stepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
            const stepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
            const prevBtn = document.getElementById('quick-prev-btn');
            const nextBtn = document.getElementById('quick-next-btn');
            const submitBtn = document.getElementById('quick-submit-btn');

            const photoInput = document.getElementById('photo');
            const openCameraBtn = document.getElementById('open-camera-btn');
            const uploadPhotoBtn = document.getElementById('upload-photo-btn');
            const photoFileName = document.getElementById('photo-file-name');
            const cameraStatus = document.getElementById('camera-status');
            const cameraPanel = document.getElementById('camera-panel');
            const cameraVideo = document.getElementById('camera-video');
            const cameraCanvas = document.getElementById('camera-canvas');
            const cameraSelectWrapper = document.getElementById('camera-select-wrapper');
            const cameraDeviceSelect = document.getElementById('camera-device-select');
            const capturePhotoBtn = document.getElementById('capture-photo-btn');
            const closeCameraBtn = document.getElementById('close-camera-btn');
            const photoPreviewWrapper = document.getElementById('photo-preview-wrapper');
            const photoPreview = document.getElementById('photo-preview');
            const categorySelect = document.getElementById('category_id');
            const visibilitySelect = document.getElementById('visibility');
            const summaryInput = document.getElementById('issue_summary');
            const detailsInput = document.getElementById('details');

            const reviewCategory = document.getElementById('review-category');
            const reviewVisibility = document.getElementById('review-visibility');
            const reviewSummary = document.getElementById('review-summary');
            const reviewDetails = document.getElementById('review-details');

            let currentStep = 0;
            let cameraStream = null;
            let previewUrl = null;
            let selectedCameraDeviceId = '';
            let availableCameras = [];

            const stopCamera = () => {
                if (cameraStream) {
                    cameraStream.getTracks().forEach((track) => track.stop());
                    cameraStream = null;
                }

                if (cameraVideo) {
                    cameraVideo.srcObject = null;
                }

                cameraPanel.classList.add('hidden');
            };

            const refreshCameraDevices = async (preferredDeviceId = null) => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                    cameraSelectWrapper.classList.add('hidden');
                    return;
                }

                try {
                    availableCameras = (await navigator.mediaDevices.enumerateDevices())
                        .filter((device) => device.kind === 'videoinput');
                } catch (error) {
                    availableCameras = [];
                }

                if (!availableCameras.length) {
                    cameraSelectWrapper.classList.add('hidden');
                    return;
                }

                cameraDeviceSelect.innerHTML = availableCameras
                    .map((device, index) => {
                        const label = device.label && device.label.trim() !== ''
                            ? device.label
                            : `Camera ${index + 1}`;
                        return `<option value="${device.deviceId}">${label}</option>`;
                    })
                    .join('');

                const preferredExists = preferredDeviceId
                    && availableCameras.some((device) => device.deviceId === preferredDeviceId);
                const currentExists = selectedCameraDeviceId
                    && availableCameras.some((device) => device.deviceId === selectedCameraDeviceId);

                if (preferredExists) {
                    selectedCameraDeviceId = preferredDeviceId;
                } else if (!currentExists) {
                    selectedCameraDeviceId = availableCameras[0].deviceId;
                }

                cameraDeviceSelect.value = selectedCameraDeviceId;
                cameraSelectWrapper.classList.toggle('hidden', availableCameras.length < 2);
            };

            const setPreviewFromFile = (file) => {
                if (!file) {
                    photoFileName.textContent = 'No photo selected';
                    photoPreviewWrapper.classList.add('hidden');
                    photoPreview.removeAttribute('src');
                    return;
                }

                photoFileName.textContent = file.name;
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }
                previewUrl = URL.createObjectURL(file);
                photoPreview.src = previewUrl;
                photoPreviewWrapper.classList.remove('hidden');
            };

            const setCapturedFileToInput = (file) => {
                if (!window.DataTransfer) {
                    cameraStatus.textContent = 'Camera capture is not supported on this browser. Please use Upload Picture.';
                    return;
                }

                const transfer = new DataTransfer();
                transfer.items.add(file);
                photoInput.files = transfer.files;
                setPreviewFromFile(file);
            };

            const startCamera = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    cameraStatus.textContent = 'No camera detected on this browser. Use Upload Picture instead.';
                    return;
                }

                cameraStatus.textContent = 'Requesting camera access...';
                stopCamera();

                try {
                    const constraints = selectedCameraDeviceId
                        ? { video: { deviceId: { exact: selectedCameraDeviceId } }, audio: false }
                        : { video: { facingMode: { ideal: 'environment' } }, audio: false };

                    cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
                } catch (error) {
                    try {
                        cameraStream = await navigator.mediaDevices.getUserMedia({
                            video: true,
                            audio: false,
                        });
                    } catch (fallbackError) {
                        cameraStatus.textContent = 'No camera detected or permission denied. Use Upload Picture instead.';
                        return;
                    }
                }

                cameraVideo.srcObject = cameraStream;
                const activeDeviceId = cameraStream.getVideoTracks()[0]?.getSettings()?.deviceId || null;
                await refreshCameraDevices(activeDeviceId);
                cameraPanel.classList.remove('hidden');
                cameraStatus.textContent = 'Camera ready. Capture when the issue is in frame.';
            };

            const captureCurrentFrame = () => {
                if (!cameraStream || !cameraVideo.videoWidth || !cameraVideo.videoHeight) {
                    cameraStatus.textContent = 'Camera not ready yet. Please wait a moment.';
                    return;
                }

                cameraCanvas.width = cameraVideo.videoWidth;
                cameraCanvas.height = cameraVideo.videoHeight;
                const context = cameraCanvas.getContext('2d');
                context.drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);

                cameraCanvas.toBlob((blob) => {
                    if (!blob) {
                        cameraStatus.textContent = 'Could not capture photo. Try again or use Upload Picture.';
                        return;
                    }

                    const capturedFile = new File([blob], `quick-ticket-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    setCapturedFileToInput(capturedFile);
                    cameraStatus.textContent = 'Photo captured successfully.';
                    stopCamera();
                }, 'image/jpeg', 0.92);
            };

            const setStep = (step) => {
                currentStep = Math.max(0, Math.min(step, stepPanels.length - 1));

                stepPanels.forEach((panel, index) => {
                    panel.classList.toggle('hidden', index !== currentStep);
                });

                stepIndicators.forEach((indicator, index) => {
                    if (index === currentStep) {
                        indicator.className = 'rounded-lg px-2 py-2 font-semibold bg-blue-600 text-white';
                    } else if (index < currentStep) {
                        indicator.className = 'rounded-lg px-2 py-2 font-semibold bg-emerald-100 text-emerald-700';
                    } else {
                        indicator.className = 'rounded-lg px-2 py-2 font-semibold bg-base-200 text-base-content/60';
                    }
                });

                prevBtn.classList.toggle('hidden', currentStep === 0);
                nextBtn.classList.toggle('hidden', currentStep === stepPanels.length - 1);
                submitBtn.classList.toggle('hidden', currentStep !== stepPanels.length - 1);

                if (currentStep !== 0) {
                    stopCamera();
                }

                if (currentStep === 2) {
                    reviewCategory.textContent = categorySelect.options[categorySelect.selectedIndex]?.text || '-';
                    reviewVisibility.textContent = visibilitySelect.options[visibilitySelect.selectedIndex]?.text || '-';
                    reviewSummary.textContent = summaryInput.value.trim() || '-';
                    reviewDetails.textContent = detailsInput.value.trim() || 'Not provided';
                }
            };

            const validateStep = () => {
                if (currentStep === 0) {
                    if (!photoInput.files || !photoInput.files.length) {
                        photoInput.reportValidity();
                        return false;
                    }
                }

                if (currentStep === 1) {
                    if (!categorySelect.value) {
                        categorySelect.reportValidity();
                        return false;
                    }

                    if (!summaryInput.value.trim()) {
                        summaryInput.reportValidity();
                        return false;
                    }
                }

                return true;
            };

            photoInput.addEventListener('change', () => {
                const file = photoInput.files && photoInput.files[0] ? photoInput.files[0] : null;
                setPreviewFromFile(file);
            });

            openCameraBtn.addEventListener('click', () => {
                startCamera();
            });

            uploadPhotoBtn.addEventListener('click', () => {
                stopCamera();
                photoInput.click();
            });

            cameraDeviceSelect.addEventListener('change', async () => {
                selectedCameraDeviceId = cameraDeviceSelect.value;
                if (!cameraPanel.classList.contains('hidden')) {
                    await startCamera();
                }
            });

            capturePhotoBtn.addEventListener('click', () => {
                captureCurrentFrame();
            });

            closeCameraBtn.addEventListener('click', () => {
                stopCamera();
                cameraStatus.textContent = 'Camera closed. You can reopen or upload a picture.';
            });

            nextBtn.addEventListener('click', () => {
                if (!validateStep()) {
                    return;
                }
                setStep(currentStep + 1);
            });

            prevBtn.addEventListener('click', () => setStep(currentStep - 1));

            form.addEventListener('submit', (event) => {
                if (currentStep !== 2) {
                    event.preventDefault();
                    return;
                }
                if (!validateStep()) {
                    event.preventDefault();
                }
            });

            const hasStepTwoErrors = @json($errors->has('category_id') || $errors->has('issue_summary') || $errors->has('details') || $errors->has('visibility'));
            const hasStepOneErrors = @json($errors->has('photo'));
            setStep(hasStepTwoErrors ? 1 : (hasStepOneErrors ? 0 : 0));
            refreshCameraDevices();

            window.addEventListener('beforeunload', () => {
                stopCamera();
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }
            });
        })();
    </script>
</x-app-layout>
