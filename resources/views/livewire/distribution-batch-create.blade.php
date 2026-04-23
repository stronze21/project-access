<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            {{ $isEdit ? 'Edit Distribution Batch' : 'Create New Distribution Batch' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ $isEdit ? 'Update the details of an existing distribution batch' : 'Schedule a new distribution batch for an ayuda program' }}
        </p>
    </div>

    <x-mary-card>
        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Program Selection -->
                <div class="md:col-span-2">
                    <x-mary-select label="Ayuda Program" wire:model="ayudaProgramId" required :options="$programs"
                        placeholder="Select program" placeholder-value="">
                    </x-mary-select>
                    @error('ayudaProgramId')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Location -->
                <div class="md:col-span-2">
                    <x-mary-input label="Distribution Location" placeholder="Enter location (e.g., Barangay Hall)"
                        wire:model="location" required />
                    @error('location')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Date and Time -->
                <div>
                    <x-mary-datetime label="Batch Date" wire:model="batchDate" required />
                    @error('batchDate')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-mary-datetime type="time" label="Start Time" wire:model="startTime" required />
                        @error('startTime')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <x-mary-datetime type="time" label="End Time" wire:model="endTime" required />
                        @error('endTime')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Target Beneficiaries -->
                <div>
                    <x-mary-input type="number" label="Target Beneficiaries"
                        placeholder="Number of intended recipients" wire:model="targetBeneficiaries" min="1"
                        required />
                    @error('targetBeneficiaries')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <x-mary-textarea label="Notes" placeholder="Additional information about this distribution batch"
                        wire:model="notes" rows="3" />
                    @error('notes')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <x-mary-button label="Cancel" class="tagged-color btn-secondary btn-outline btn-secline"
                    wire:click="cancel" type="button" />
                <x-mary-button type="submit" label="{{ $isEdit ? 'Update Batch' : 'Create Batch' }}" />
            </div>
        </form>
    </x-mary-card>
</div>
