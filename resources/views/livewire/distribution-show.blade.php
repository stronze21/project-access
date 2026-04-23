<div>
    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 mb-6 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Distribution Details</h1>
            <p class="mt-1 text-sm text-gray-600">Reference: {{ $distribution->reference_number }}</p>
        </div>
        <div class="flex space-x-2">
            @if ($distribution->status === 'pending')
                <x-mary-dropdown width="w-56">
                    <x-slot name="trigger">
                        <x-mary-button icon="o-check-circle" label="Change Status" />
                    </x-slot>

                    <div class="px-4 py-2 text-xs text-gray-400">Update Status</div>

                    <x-mary-menu-item wire:click="updateStatus('verified')" icon="o-clipboard-document-check">
                        Mark as Verified
                    </x-mary-menu-item>

                    <x-mary-menu-item wire:click="updateStatus('distributed')" icon="o-check-circle">
                        Mark as Distributed
                    </x-mary-menu-item>

                    <x-mary-menu-item wire:click="updateStatus('rejected')" icon="o-x-circle">
                        Mark as Rejected
                    </x-mary-menu-item>

                    <x-mary-menu-item wire:click="updateStatus('cancelled')" icon="o-exclamation-triangle">
                        Mark as Cancelled
                    </x-mary-menu-item>
                </x-mary-dropdown>
            @elseif($distribution->status === 'verified')
                <x-mary-button wire:click="updateStatus('distributed')" class="tagged-color btn-success"
                    icon="o-check-circle">
                    Mark as Distributed
                </x-mary-button>
            @endif

            <x-mary-button link="{{ route('distributions.index') }}"
                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left" label="Back to List" />
        </div>
    </div>

    <!-- Status Banner -->
    <div
        class="mb-6 p-4 rounded-lg border
        @if ($distribution->status === 'distributed') bg-green-50 border-green-200
        @elseif($distribution->status === 'verified') bg-blue-50 border-blue-200
        @elseif($distribution->status === 'pending') bg-yellow-50 border-yellow-200
        @else bg-red-50 border-red-200 @endif">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if ($distribution->status === 'distributed')
                    <svg class="w-5 h-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                @elseif($distribution->status === 'verified')
                    <svg class="w-5 h-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                @elseif($distribution->status === 'pending')
                    <svg class="w-5 h-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                @else
                    <svg class="w-5 h-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
            <div class="ml-3">
                <h3
                    class="text-sm font-medium
                    @if ($distribution->status === 'distributed') text-green-800
                    @elseif($distribution->status === 'verified') text-blue-800
                    @elseif($distribution->status === 'pending') text-yellow-800
                    @else text-red-800 @endif">
                    Status: {{ ucfirst($distribution->status) }}
                </h3>
                <div
                    class="mt-2 text-sm
                    @if ($distribution->status === 'distributed') text-green-700
                    @elseif($distribution->status === 'verified') text-blue-700
                    @elseif($distribution->status === 'pending') text-yellow-700
                    @else text-red-700 @endif">
                    @if ($distribution->status === 'distributed')
                        <p>This aid has been successfully distributed to the beneficiary.</p>
                    @elseif($distribution->status === 'verified')
                        <p>This distribution has been verified and is ready for final distribution.</p>
                    @elseif($distribution->status === 'pending')
                        <p>This distribution is pending verification or final distribution.</p>
                    @elseif($distribution->status === 'rejected')
                        <p>This distribution has been rejected.</p>
                    @else
                        <p>This distribution has been cancelled.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Distribution Details -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Main Info Card -->
        <div class="lg:col-span-2">
            <x-mary-card title="Distribution Information">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Distribution Details</h3>
                        <dl class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Program:</dt>
                                <dd class="font-medium text-gray-900">
                                    <a href="{{ route('programs.show', $distribution->ayuda_program_id) }}"
                                        class="text-blue-600 hover:underline">
                                        {{ $distribution->ayudaProgram->name }}
                                    </a>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Reference Number:</dt>
                                <dd class="font-medium text-gray-900">{{ $distribution->reference_number }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Distribution Date:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $distribution->distribution_date->format('F d, Y') }}</dd>
                            </div>
                            @if ($distribution->batch)
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Batch:</dt>
                                    <dd class="font-medium text-gray-900">
                                        <a href="{{ route('distributions.batches.show', $distribution->batch_id) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ $distribution->batch->batch_number }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Created:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $distribution->created_at->format('M d, Y h:i A') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Aid Details</h3>
                        <dl class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Amount:</dt>
                                <dd class="font-medium text-gray-900">₱{{ number_format($distribution->amount, 2) }}
                                </dd>
                            </div>
                            @if ($distribution->goods_details)
                                <div>
                                    <dt class="text-gray-600">Goods Details:</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $distribution->goods_details }}</dd>
                                </div>
                            @endif
                            @if ($distribution->services_details)
                                <div>
                                    <dt class="text-gray-600">Services Details:</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $distribution->services_details }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3 class="text-sm font-medium text-gray-500">Notes</h3>

                        @can('create-distributions')
                            @if (!$isEditingNotes)
                                <x-mary-button wire:click="editNotes" label="Edit Notes" icon="o-pencil-square"
                                    class="btn-outline btn-sm" />
                            @endif
                        @endcan
                    </div>

                    @if ($isEditingNotes)
                        <x-mary-textarea wire:model="notes" placeholder="Add notes about this distribution" rows="3"
                            hint="Maximum of 1,000 characters" />

                        <div class="flex gap-2 mt-3">
                            <x-mary-button wire:click="saveNotes" label="Save" icon="o-check" class="btn-primary" />
                            <x-mary-button wire:click="cancelEditNotes" label="Cancel" icon="o-x-mark"
                                class="btn-ghost" />
                        </div>
                    @elseif($distribution->notes)
                        <div class="prose-sm prose max-w-none">
                            {{ $distribution->notes }}
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No notes added for this distribution yet.</p>
                    @endif
                </div>

                <div class="pt-6 mt-6 border-t">
                    <h3 class="mb-2 text-sm font-medium text-gray-500">Processing Information</h3>
                    <dl class="mt-2 space-y-1 text-sm">
                        @if ($distribution->distributed_by)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Distributed By:</dt>
                                <dd class="font-medium text-gray-900">{{ $distribution->distributor->name }}</dd>
                            </div>
                        @endif
                        @if ($distribution->verified_by)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Verified By:</dt>
                                <dd class="font-medium text-gray-900">{{ $distribution->verifier->name }}</dd>
                            </div>
                        @endif
                        @if ($distribution->verification_data)
                            <div>
                                <dt class="mb-1 text-gray-600">Verification Data:</dt>
                                <dd class="font-medium text-gray-900">
                                    <pre class="p-2 overflow-x-auto text-xs rounded bg-base-50">{{ json_encode($distribution->verification_data, JSON_PRETTY_PRINT) }}</pre>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($distribution->receipt_path)
                    <div class="pt-6 mt-6 border-t">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Receipt/Proof</h3>
                        <div class="mt-2">
                            <img src="{{ Storage::url($distribution->receipt_path) }}" alt="Receipt"
                                class="object-contain h-64 rounded">
                        </div>
                    </div>
                @endif
            </x-mary-card>
        </div>

        <!-- Beneficiary Card -->
        <div>
            <x-mary-card title="Beneficiary Information">
                <!-- Resident Info -->
                <div class="mb-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            @if ($distribution->resident->photo_path)
                                <img src="{{ Storage::url($distribution->resident->photo_path) }}"
                                    alt="{{ $distribution->resident->full_name }}"
                                    class="object-cover w-10 h-10 rounded-full">
                            @else
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-base-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">
                                <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->resident->full_name }}
                                </a>
                            </h3>
                            <p class="text-xs text-gray-500">
                                {{ $distribution->resident->getAge() }} years,
                                {{ ucfirst($distribution->resident->gender) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-1 text-sm">
                        <p><span class="font-medium">ID:</span> {{ $distribution->resident->resident_id }}</p>
                        @if ($distribution->resident->contact_number)
                            <p><span class="font-medium">Contact:</span> {{ $distribution->resident->contact_number }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Household Info -->
                @if ($distribution->household)
                    <div class="pt-4 border-t">
                        <h3 class="mb-2 text-sm font-medium">Household Information</h3>
                        <div class="space-y-1 text-sm">
                            <p>
                                <span class="font-medium">Household ID:</span>
                                <a href="{{ route('households.show', $distribution->household_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->household->household_id }}
                                </a>
                            </p>
                            <p><span class="font-medium">Address:</span> {{ $distribution->household->address }}</p>
                            <p><span class="font-medium">Barangay:</span> {{ $distribution->household->barangay }}</p>
                            <p><span class="font-medium">Members:</span> {{ $distribution->household->member_count }}
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Prior Distributions -->
                <div class="pt-4 mt-6 border-t">
                    <h3 class="mb-2 text-sm font-medium">Prior Distributions</h3>

                    @php
                        $priorDistributions = \App\Models\Distribution::where('resident_id', $distribution->resident_id)
                            ->where('id', '!=', $distribution->id)
                            ->where('status', 'distributed')
                            ->orderByDesc('distribution_date')
                            ->limit(5)
                            ->get();
                    @endphp

                    @if ($priorDistributions->count() > 0)
                        <ul class="space-y-2 text-sm">
                            @foreach ($priorDistributions as $prior)
                                <li class="p-2 rounded bg-base-50">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <a href="{{ route('distributions.show', $prior->id) }}"
                                                class="font-medium text-blue-600 hover:underline">
                                                {{ $prior->reference_number }}
                                            </a>
                                            <p class="text-xs text-gray-500">
                                                {{ $prior->distribution_date->format('M d, Y') }}</p>
                                        </div>
                                        <span
                                            class="text-sm font-medium">₱{{ number_format($prior->amount, 2) }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-600">{{ $prior->ayudaProgram->name }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No prior distributions found for this resident.</p>
                    @endif
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
