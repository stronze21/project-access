<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Photo and Signature Manager</h1>
            <p class="mt-1 text-sm text-gray-600">Validate resident filenames, preview images, and import them in batches.</p>
        </div>
        <x-mary-button link="{{ route('residents.index') }}" icon="o-arrow-left" label="Back to Residents"
            class="btn-secondary btn-outline" />
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-mary-card title="Resident Photos" subtitle="Required filename: {resident_id}.jpg, {resident_id}.jpeg, or {resident_id}.png">
            <div x-data="{
                    uploading: false,
                    progress: 0,
                    names: [],
                    error: '',
                    validateSelection(files, input) {
                        const selected = Array.from(files);
                        const totalBytes = selected.reduce((total, file) => total + file.size, 0);

                        if (selected.length > {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }}) {
                            this.error = 'Too many files selected. Choose up to {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }} photo files in one batch.';
                        } else if (totalBytes > {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB * 1024 * 1024 }}) {
                            this.error = 'This batch is too large. Keep the combined file size at or below {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB }} MB.';
                        } else {
                            this.error = '';
                            this.names = selected.map(file => file.name);
                            return true;
                        }

                        input.value = '';
                        this.names = [];
                        this.progress = 0;
                        return false;
                    }
                }"
                x-on:livewire-upload-start="uploading = true; progress = 0; error = ''"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                x-on:livewire-upload-finish="uploading = false; progress = 100"
                x-on:livewire-upload-error="uploading = false; progress = 0; error = 'The server could not receive this batch. Check the file count and size limits, then select the files again.'">
                <label class="relative flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-blue-300 bg-blue-50/50 p-6 text-center transition hover:border-blue-500 hover:bg-blue-50"
                    x-on:dragover.prevent
                    x-on:drop.prevent="if (validateSelection($event.dataTransfer.files, $refs.photoInput)) { $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true })) }">
                    <x-mary-icon name="o-photo" class="w-10 h-10 text-blue-500" />
                    <strong class="mt-3 text-gray-800">Drop resident photos here</strong>
                    <span class="mt-1 text-sm text-gray-500">or click to select up to {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }} JPG/JPEG/PNG files (10 MB each, {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB }} MB total)</span>
                    <input x-ref="photoInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        type="file" accept="image/jpeg,image/png" multiple wire:model.live="photoFiles"
                        x-on:change.capture="if (!validateSelection($event.target.files, $event.target)) { $event.stopImmediatePropagation() }">
                </label>
                <div x-cloak x-show="error" class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                    <strong class="block">Photo upload could not be completed</strong>
                    <span class="mt-1 block" x-text="error"></span>
                </div>
                @error('photoFiles')
                    <div class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                        <strong class="block">Photo upload could not be completed</strong>
                        <span class="mt-1 block">
                            {{ str_contains($message, 'photoFiles')
                                ? 'The server rejected this batch before it could validate the filenames. Select no more than 20 files, keep each file at 10 MB or less, and keep the whole batch at 60 MB or less.'
                                : $message }}
                        </span>
                    </div>
                @enderror

                <div x-show="names.length" class="mt-4 space-y-2">
                    <template x-for="name in names" :key="name">
                        <div class="rounded-lg border p-3">
                            <div class="flex justify-between gap-3 text-xs"><span class="truncate" x-text="name"></span><span x-text="`${progress}%`"></span></div>
                            <progress class="progress progress-primary mt-2 w-full" :value="progress" max="100"></progress>
                        </div>
                    </template>
                </div>
            </div>

            @if (count($photoResults))
                <div class="mt-5 flex snap-x gap-4 overflow-x-auto pb-3">
                    @foreach ($photoResults as $index => $result)
                        <article class="w-64 flex-none snap-start overflow-hidden rounded-xl border bg-white shadow-sm">
                            <div class="aspect-square bg-gray-100">
                                @if (isset($photoFiles[$index]) && $result['previewable'])
                                    <img src="{{ $photoFiles[$index]->temporaryUrl() }}" alt="{{ $result['file_name'] }}"
                                        class="w-full h-full object-cover">
                                @endif
                            </div>
                            @include('livewire.partials.resident-media-result', ['result' => $result])
                        </article>
                    @endforeach
                </div>
                <div class="flex flex-wrap justify-between gap-2 mt-4">
                    <x-mary-button wire:click="clearPhotos" label="Clear" class="btn-secondary btn-outline" />
                    <x-mary-button wire:click="importPhotos" wire:confirm="Import all valid resident photos? Existing photos for matched residents will be replaced."
                        spinner="importPhotos" label="Import Valid Photos" icon="o-arrow-up-tray" class="btn-primary" />
                </div>
            @endif
        </x-mary-card>

        <x-mary-card title="Resident Signatures" subtitle="Required filename: {resident_id}_signature.png">
            <div x-data="{
                    uploading: false,
                    progress: 0,
                    names: [],
                    error: '',
                    validateSelection(files, input) {
                        const selected = Array.from(files);
                        const totalBytes = selected.reduce((total, file) => total + file.size, 0);

                        if (selected.length > {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }}) {
                            this.error = 'Too many files selected. Choose up to {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }} signature files in one batch.';
                        } else if (totalBytes > {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB * 1024 * 1024 }}) {
                            this.error = 'This batch is too large. Keep the combined file size at or below {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB }} MB.';
                        } else {
                            this.error = '';
                            this.names = selected.map(file => file.name);
                            return true;
                        }

                        input.value = '';
                        this.names = [];
                        this.progress = 0;
                        return false;
                    }
                }"
                x-on:livewire-upload-start="uploading = true; progress = 0; error = ''"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                x-on:livewire-upload-finish="uploading = false; progress = 100"
                x-on:livewire-upload-error="uploading = false; progress = 0; error = 'The server could not receive this batch. Check the file count and size limits, then select the files again.'">
                <label class="relative flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-teal-300 bg-teal-50/50 p-6 text-center transition hover:border-teal-500 hover:bg-teal-50"
                    x-on:dragover.prevent
                    x-on:drop.prevent="if (validateSelection($event.dataTransfer.files, $refs.signatureInput)) { $refs.signatureInput.files = $event.dataTransfer.files; $refs.signatureInput.dispatchEvent(new Event('change', { bubbles: true })) }">
                    <x-mary-icon name="o-pencil-square" class="w-10 h-10 text-teal-600" />
                    <strong class="mt-3 text-gray-800">Drop resident signatures here</strong>
                    <span class="mt-1 text-sm text-gray-500">or click to select up to {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_FILES_PER_BATCH }} PNG files (10 MB each, {{ \App\Livewire\ResidentPhotoSignatureManager::MAX_BATCH_SIZE_MB }} MB total)</span>
                    <input x-ref="signatureInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        type="file" accept="image/png" multiple wire:model.live="signatureFiles"
                        x-on:change.capture="if (!validateSelection($event.target.files, $event.target)) { $event.stopImmediatePropagation() }">
                </label>
                <div x-cloak x-show="error" class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                    <strong class="block">Signature upload could not be completed</strong>
                    <span class="mt-1 block" x-text="error"></span>
                </div>
                @error('signatureFiles')
                    <div class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="alert">
                        <strong class="block">Signature upload could not be completed</strong>
                        <span class="mt-1 block">
                            {{ str_contains($message, 'signatureFiles')
                                ? 'The server rejected this batch before it could validate the filenames. Select no more than 20 files, keep each file at 10 MB or less, and keep the whole batch at 60 MB or less.'
                                : $message }}
                        </span>
                    </div>
                @enderror

                <div x-show="names.length" class="mt-4 space-y-2">
                    <template x-for="name in names" :key="name">
                        <div class="rounded-lg border p-3">
                            <div class="flex justify-between gap-3 text-xs"><span class="truncate" x-text="name"></span><span x-text="`${progress}%`"></span></div>
                            <progress class="progress progress-success mt-2 w-full" :value="progress" max="100"></progress>
                        </div>
                    </template>
                </div>
            </div>

            @if (count($signatureResults))
                <div class="mt-5 flex snap-x gap-4 overflow-x-auto pb-3">
                    @foreach ($signatureResults as $index => $result)
                        <article class="w-72 flex-none snap-start overflow-hidden rounded-xl border bg-white shadow-sm">
                            <div class="flex aspect-[2/1] items-center justify-center bg-gray-50 p-4">
                                @if (isset($signatureFiles[$index]) && $result['previewable'])
                                    <img src="{{ $signatureFiles[$index]->temporaryUrl() }}" alt="{{ $result['file_name'] }}"
                                        class="max-w-full max-h-full object-contain">
                                @endif
                            </div>
                            @include('livewire.partials.resident-media-result', ['result' => $result])
                        </article>
                    @endforeach
                </div>
                <div class="flex flex-wrap justify-between gap-2 mt-4">
                    <x-mary-button wire:click="clearSignatures" label="Clear" class="btn-secondary btn-outline" />
                    <x-mary-button wire:click="importSignatures" wire:confirm="Import all valid signatures? Existing signatures for matched residents will be replaced."
                        spinner="importSignatures" label="Import Valid Signatures" icon="o-arrow-up-tray" class="btn-success" />
                </div>
            @endif
        </x-mary-card>
    </div>

    <x-mary-card title="Filename Rules and Validation">
        <div class="grid gap-4 text-sm md:grid-cols-3">
            <div><strong class="block text-gray-800">Exact resident ID</strong><span class="text-gray-600">The filename portion must exactly match an existing resident ID.</span></div>
            <div><strong class="block text-gray-800">Invalid files are skipped</strong><span class="text-gray-600">Unknown IDs, wrong formats, and duplicates are flagged and never imported.</span></div>
            <div><strong class="block text-gray-800">Existing media</strong><span class="text-gray-600">Importing a valid file replaces that resident's existing photo or signature.</span></div>
        </div>
    </x-mary-card>
</div>
