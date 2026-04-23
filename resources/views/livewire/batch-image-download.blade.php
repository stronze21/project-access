<div>
    <x-mary-card title="Batch Image Download">
        <div class="mb-6">
            <p class="text-sm text-gray-600">Download multiple images at once for selected residents. Images will be
                organized in a ZIP file, with a separate folder for each resident.</p>
        </div>

        <!-- Image Type Selection -->
        <div class="mb-6">
            <h3 class="mb-2 text-base font-medium text-gray-700">Select image types to include:</h3>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach ($availableImageTypes as $type => $label)
                    <div>
                        <x-mary-checkbox wire:model.live="selectedImageTypes.{{ $type }}"
                            label="{{ $label }}" />
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Selected Residents Count -->
        <div class="p-4 mb-6 rounded-md bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-gray-700">Selected Residents:</span>
                    <span class="ml-2 text-base font-semibold">{{ count($selectedResidents) }}</span>
                </div>
                <x-mary-button wire:click="downloadImages" icon="o-arrow-down-tray" label="Download Images"
                    :disabled="count($selectedResidents) === 0" />
            </div>
        </div>

        <x-slot:actions>
            <div class="flex justify-between">
                <x-mary-button link="{{ route('residents.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline" label="Back to Residents" />
            </div>
        </x-slot:actions>
    </x-mary-card>
</div>
