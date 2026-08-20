<div
    x-data="{
        focusResidentSearch(attempt = 0) {
            setTimeout(() => {
                const field = this.$refs.residentSearch;
                if (field && !field.disabled) {
                    field.focus();
                    return;
                }

                if (attempt < 10) this.focusResidentSearch(attempt + 1);
            }, 100);
        }
    }"
    x-effect="if ($wire.autoFocusSearch && !$wire.selectedResident) focusResidentSearch()"
    x-on:focus-resident-search.window="focusResidentSearch()">
    <x-mary-card title="Ayuda Distribution">
        <x-slot:menu>
            <x-mary-button link="{{ route('distributions.index') }}" label="Distribution History"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
            <x-mary-button link="{{ route('distributions.batches') }}" label="Manage Batches"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
        </x-slot:menu>

        <!-- Distribution Mode Selection -->
        <div class="p-4 mb-6 rounded-lg bg-base-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-medium">Distribution Mode</h3>
                    <p class="text-sm text-gray-600">Select how you want to distribute aid</p>
                </div>
                <div class="flex items-center space-x-4">
                    <x-mary-toggle label="Continues Distribution" wire:model.live="continuesDistribution"
                        hint="{{ $continuesDistribution ? 'Process multiple beneficiaries' : 'Single distribution' }}" />
                    <x-mary-toggle label="Batch Mode" wire:model.live="isBatchMode"
                        hint="{{ $isBatchMode ? 'Distribution from a scheduled batch' : 'Individual distribution' }}" />
                </div>
            </div>

            @if ($distributionCount > 0)
                <div class="p-2 mt-3 text-blue-800 rounded bg-blue-50">
                    <p><span class="font-medium">{{ $distributionCount }}</span> distributions completed in this session
                    </p>
                </div>
            @endif

            @if ($isBatchMode && $selectedBatchId && $batchProgress > 0)
                <div class="mt-3">
                    <p class="mb-1 text-sm text-gray-600">Batch Progress: {{ $batchProgress }}%</p>
                    <div class="w-full h-2 overflow-hidden bg-gray-200 rounded-full">
                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ $batchProgress }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Program and Batch Selection -->
        <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-{{ $isBatchMode ? '2' : '1' }} gap-4">
                <div>
                    <label for="selectedProgramId" class="pt-0 font-semibold label label-text">
                        <span>
                            Ayuda Program<span class="text-error">*</span>
                        </span>
                    </label>
                    <select wire:model.live="selectedProgramId" id="selectedProgramId"
                        placeholder="Select an Ayuda program" class="w-full font-normal select select-primary" required>
                        <option value>
                            {{ $selectedProgramId ? 'Type: ' . ucfirst($programType) . ($programAmount > 0 ? ', Amount: ₱' . number_format($programAmount, 2) : '') : 'Select a program to distribute' }}
                        </option>
                        @foreach ($availablePrograms as $program)
                            <option value="{{ $program->id }}">
                                {{ $program->name }}
                                {{ $program->amount > 0 ? '(₱' . number_format($program->amount, 2) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($isBatchMode)
                    <div>
                        <label for="selectedProgramId" class="pt-0 font-semibold label label-text">
                            <span>
                                Distribution Batch<span class="text-error">*</span>
                            </span>
                        </label>
                        <select wire:model.live="selectedBatchId" id="selectedBatchId"
                            class="w-full font-normal select select-primary" required
                            :disabled="{{ count($availableBatches) === 0 || !$selectedProgramId }}">
                            <option value>
                                {{ count($availableBatches) > 0 ? 'Select a batch' : 'No batches available' }}
                            </option>
                            @foreach ($availableBatches as $batch)
                                <option value="{{ $batch->id }}">
                                    {{ $batch->batch_number }} ({{ $batch->batch_date->format('M d, Y') }}) -
                                    {{ $batch->location }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>

        <!-- Resident Identification Section -->
        <div class="mb-6 overflow-hidden border rounded-lg">
            <div class="px-4 py-3 border-b bg-base-50">
                <h3 class="font-medium">Beneficiary Identification</h3>
                <p class="text-sm text-gray-600">Scan QR code, RFID or search for resident</p>
            </div>

            <div class="p-4">
                <div class="flex flex-col gap-4 mb-2 md:flex-row">
                    <div class="flex-1">
                        <x-mary-input x-ref="residentSearch" label="Search Resident" wire:model="searchQuery"
                            placeholder="Enter name, ID or scan QR/RFID..." wire:keydown.enter="searchResident"
                            :disabled="(bool) $selectedResident" />
                    </div>
                    <div class="flex items-end space-x-2">
                        <x-mary-button wire:click="searchResident" icon="o-magnifying-glass"
                            :disabled="(bool) $selectedResident">Search</x-mary-button>
                        <x-mary-button wire:click="$toggle('showScanner')"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-qr-code"
                            :disabled="(bool) $selectedResident">
                            {{ $showScanner ? 'Hide Scanner' : 'Scan QR/RFID' }}
                        </x-mary-button>
                        <x-mary-button wire:click="distribute"
                            label="{{ $isVerificationRequired ? 'Submit for Verification' : 'Complete Distribution' }}"
                            icon="o-check-circle" :disabled="!$selectedResident || (!$isEligible && !$isVerificationRequired)"
                            wire:loading.attr="disabled" wire:target="distribute" />
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 mb-4 text-sm cursor-pointer">
                    <input type="checkbox" wire:model.live="autoFocusSearch" class="checkbox checkbox-primary checkbox-sm"
                        x-on:change="if ($event.target.checked) focusResidentSearch()">
                    <span>Auto-focus resident search</span>
                </label>

                @if ($showScanner)
                    <div class="mb-4">
                        <livewire:qr-rfid-scanner :autoProcess="false" />
                    </div>
                @endif

                @if ($selectedResident)
                    <div class="max-w-2xl mx-auto mb-4" data-testid="scanned-resident-id-card">
                        <div class="relative overflow-hidden bg-gray-100 border border-gray-200 rounded-xl shadow-lg"
                            style="aspect-ratio: 3.375 / 2.125;">
                            <iframe
                                src="{{ route('residents.id-card.landscape', ['id' => $selectedResident->id, 'preview' => 'front']) }}"
                                title="Front ID card of {{ $selectedResident->full_name }}"
                                class="absolute inset-0 w-full h-full border-0"
                                loading="eager"></iframe>
                        </div>
                        <div class="flex justify-end mt-2">
                            <x-mary-button wire:click="clearResident" icon="o-x-circle"
                                class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                label="Clear resident" />
                        </div>
                    </div>

                    @if ($selectedProgramId)
                        <div
                            class="max-w-xl p-3 mx-auto mb-4 text-sm rounded-md {{ $isEligible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <p class="font-medium">
                                {{ $isEligible ? '✓ Eligible' : '✗ Not Eligible' }}:
                                {{ $eligibilityMessage }}
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Distribution Details Form -->
        <form wire:submit="distribute">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Distribution Details -->
                <div>
                    <h3 class="mb-4 font-medium">Distribution Details</h3>

                    <div class="mb-4">
                        <x-mary-datetime label="Distribution Date" wire:model="distributionDate" required
                            :disabled="$isBatchMode && $selectedBatchId" />
                    </div>

                    @if ($programType === 'cash' || $programType === 'mixed')
                        <div class="mb-4">
                            <x-mary-input label="Amount (₱)" wire:model="amount" type="number" step="0.01" required
                                hint="{{ $programAmount > 0 ? 'Default: ₱' . number_format($programAmount, 2) : 'Enter amount to distribute' }}" />
                        </div>
                    @endif

                    @if ($programType === 'goods' || $programType === 'mixed')
                        <div class="mb-4">
                            <x-mary-textarea label="Goods Details" wire:model="goodsDetails"
                                placeholder="Describe the goods distributed" rows="2" />
                        </div>
                    @endif

                    @if ($programType === 'services' || $programType === 'mixed')
                        <div class="mb-4">
                            <x-mary-textarea label="Services Details" wire:model="servicesDetails"
                                placeholder="Describe the services provided" rows="2" />
                        </div>
                    @endif

                    <div class="mb-4">
                        <x-mary-textarea label="Notes" wire:model="notes"
                            placeholder="Additional notes about this distribution" rows="2" />
                    </div>
                </div>

                <!-- Verification and Submission -->
                <div>
                    <h3 class="mb-4 font-medium">Receipt and Verification</h3>

                    <div class="mb-4">
                        <x-mary-file label="Receipt/Proof Image (optional)" wire:model="receiptImage"
                            hint="Take photo of signature or thumbmark" />

                        @if ($receiptImage)
                            <div class="mt-2">
                                <p class="mb-1 text-sm text-gray-500">Preview:</p>
                                <img src="{{ $receiptImage->temporaryUrl() }}" class="object-cover h-32 rounded" />
                            </div>
                        @endif
                    </div>

                    @if ($isVerificationRequired)
                        <div class="p-3 mb-4 border rounded-lg bg-amber-50 border-amber-200">
                            <h4 class="font-medium text-amber-800">Verification Required</h4>
                            <p class="mb-2 text-sm text-amber-700">This program requires verification before finalizing
                                distribution.</p>

                            <!-- Add any verification fields here -->
                        </div>
                    @endif

                    @if ($continuesDistribution)
                        <div class="p-3 mb-4 border border-blue-200 rounded-lg bg-blue-50">
                            <h4 class="font-medium text-blue-800">Continues Distribution Mode</h4>
                            <p class="mb-2 text-sm text-blue-700">
                                After completing this distribution, you'll be able to immediately process another
                                beneficiary
                                with the same program settings.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3">
                <x-mary-button type="button" label="Reset Form" wire:click="resetDistribution"
                    class="tagged-color btn-secondary btn-outline btn-secline" />
                <x-mary-button type="submit"
                    label="{{ $isVerificationRequired ? 'Submit for Verification' : 'Complete Distribution' }}"
                    icon="o-check-circle" :disabled="!$selectedResident || (!$isEligible && !$isVerificationRequired)" />
            </div>
        </form>

        <!-- Success Modal for Continues Distribution -->
        @if ($showSuccessModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-60">
                <div class="w-full max-w-md p-6 overflow-hidden bg-white rounded-lg shadow-xl">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-center">Distribution Completed</h3>

                    <div class="p-3 mb-4 border rounded bg-green-50">
                        <div class="mb-2">
                            <p><span class="font-medium">Name:</span> {{ $lastDistribution->resident->full_name }}</p>
                            <p><span class="font-medium">Program:</span> {{ $lastDistribution->ayudaProgram->name }}
                            </p>
                            @if ($lastDistribution->amount > 0)
                                <p><span class="font-medium">Amount:</span>
                                    ₱{{ number_format($lastDistribution->amount, 2) }}</p>
                            @endif
                            <p><span class="font-medium">Reference:</span> {{ $lastDistribution->reference_number }}
                            </p>
                        </div>
                    </div>

                    <p class="mb-4 text-sm text-center text-gray-600">Would you like to distribute to another
                        beneficiary?</p>

                    <div class="flex justify-center space-x-3">
                        <x-mary-button wire:click="resetDistribution"
                            class="tagged-color btn-secondary btn-outline btn-secline">
                            Complete & Reset All
                        </x-mary-button>
                        <x-mary-button wire:click="continueToNextDistribution" class="tagged-color btn-primary">
                            Continue to Next
                        </x-mary-button>
                    </div>
                </div>
            </div>
        @endif
    </x-mary-card>
</div>
