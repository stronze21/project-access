<div>
    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 mb-6 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $program->name }}</h1>
            <div class="flex items-center mt-1 space-x-2">
                <span class="text-sm text-gray-500">{{ $program->code }}</span>
                <span
                    class="text-xs font-medium py-1 px-2 rounded
                    @if ($program->is_active) bg-green-100 text-green-800
                    @else bg-red-100 text-red-800 @endif
                ">
                    {{ $program->is_active ? 'active' : 'inactive' }}
                </span>
                <span class="px-2 py-1 text-xs text-purple-800 bg-purple-100 rounded">
                    {{ ucfirst($program->type) }}
                </span>
            </div>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('programs.edit', $program->id) }}" icon="o-pencil" label="Edit" />
            <x-mary-button wire:click="setProgramStatus('{{ $program->is_active ? 'inactive' : 'active' }}')"
                class="{{ $program->is_active ? 'btn-error' : 'btn-success' }}"
                icon="o-{{ $program->is_active ? 'exclamation-circle' : 'check-circle' }}"
                label="{{ $program->is_active ? 'Deactivate' : 'Activate' }}" />
            <x-mary-button link="{{ route('programs.index') }}"
                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left" label="Back to List" />
        </div>
    </div>

    <!-- Program Summary Card -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Program Details -->
        <div class="lg:col-span-2">
            <x-mary-card>
                <div class="prose max-w-none">
                    <p>{{ $program->description }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Program Details</h3>
                        <dl class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Type:</dt>
                                <dd class="font-medium text-gray-900">{{ ucfirst($program->type) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Duration:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $program->start_date->format('M d, Y') }}
                                    @if ($program->end_date)
                                        - {{ $program->end_date->format('M d, Y') }}
                                    @else
                                        - Ongoing
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Frequency:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $program->frequency)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Distribution Count:</dt>
                                <dd class="font-medium text-gray-900">{{ $program->distribution_count }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Requires Verification:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $program->requires_verification ? 'Yes' : 'No' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Aid Details</h3>
                        <dl class="mt-2 space-y-2 text-sm">
                            @if ($program->amount)
                                <div class="flex justify-between">
                                    <dt class="text-gray-600">Amount per Beneficiary:</dt>
                                    <dd class="font-medium text-gray-900">₱{{ number_format($program->amount, 2) }}
                                    </dd>
                                </div>
                            @endif

                            @if ($program->goods_description)
                                <div>
                                    <dt class="text-gray-600">Goods to be Distributed:</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $program->goods_description }}</dd>
                                </div>
                            @endif

                            @if ($program->services_description)
                                <div>
                                    <dt class="text-gray-600">Services to be Provided:</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $program->services_description }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t">
                    <h3 class="mb-2 text-sm font-medium text-gray-500">Program Progress</h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        @if ($program->total_budget)
                            <div>
                                <div class="flex justify-between mb-1 text-sm">
                                    <span>Budget Utilization</span>
                                    <span>{{ $program->budget_percent }}%</span>
                                </div>
                                <div class="w-full h-3 rounded-full bg-base-200">
                                    <div class="h-3 bg-blue-600 rounded-full"
                                        style="width: {{ $program->budget_percent }}%"></div>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    ₱{{ number_format($program->budget_used, 2) }} used of
                                    ₱{{ number_format($program->total_budget, 2) }} total budget
                                </div>
                            </div>
                        @endif

                        @if ($program->max_beneficiaries)
                            <div>
                                <div class="flex justify-between mb-1 text-sm">
                                    <span>Beneficiary Allocation</span>
                                    <span>{{ $program->beneficiary_percent }}%</span>
                                </div>
                                <div class="w-full h-3 rounded-full bg-base-200">
                                    <div class="h-3 bg-green-600 rounded-full"
                                        style="width: {{ $program->beneficiary_percent }}%"></div>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ number_format($program->current_beneficiaries) }} beneficiaries of
                                    {{ number_format($program->max_beneficiaries) }} maximum
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <div class="flex space-x-3">
                        <x-mary-button link="{{ route('distributions.create') }}?program={{ $program->id }}"
                            class="tagged-color btn-primary" icon="o-banknotes">
                            Distribute Aid
                        </x-mary-button>
                        <x-mary-button link="{{ route('distributions.batches.create') }}?program={{ $program->id }}"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-users">
                            Create Batch
                        </x-mary-button>
                    </div>
                </div>
            </x-mary-card>
        </div>

        <!-- Eligibility Criteria Card -->
        <div>
            <x-mary-card title="Eligibility Criteria">
                @if ($program->eligibilityCriteria->count() > 0)
                    <div class="divide-y">
                        @foreach ($program->eligibilityCriteria as $criterion)
                            <div class="py-3">
                                <div class="flex items-start justify-between">
                                    <h4 class="font-medium">{{ $criterion->criterion_name }}</h4>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded {{ $criterion->is_required ? 'bg-red-100 text-red-800' : 'bg-base-100 text-gray-800' }}">
                                        {{ $criterion->is_required ? 'Required' : 'Optional' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ ucfirst($criterion->criterion_type) }} {{ $criterion->operator }}
                                    {{ $criterion->value }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center text-gray-500">
                        No eligibility criteria defined
                    </div>
                @endif

                <div class="pt-3 mt-4 border-t">
                    <x-mary-button link="{{ route('programs.edit', $program->id) }}"
                        class="tagged-color btn-secondary btn-outline btn-secline" size="sm" class="w-full">
                        Edit Criteria
                    </x-mary-button>
                </div>
            </x-mary-card>
        </div>
    </div>

    <!-- Distribution Analytics -->
    <x-mary-card title="Distribution Analytics" class="mb-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Daily Distribution Chart -->
            <div>
                <h3 class="mb-2 text-sm font-medium">Distribution by Date</h3>
                <div class="h-64" wire:ignore>
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            <!-- Location Distribution Chart -->
            <div>
                <h3 class="mb-2 text-sm font-medium">Distribution by Location</h3>
                <div class="h-64" wire:ignore>
                    <canvas id="locationChart"></canvas>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('livewire:initialized', function() {
                // Distribution Chart
                const distributionCtx = document.getElementById('distributionChart').getContext('2d');
                const distributionData = @json($distributionData);

                const distributionChart = new Chart(distributionCtx, {
                    type: 'bar',
                    data: {
                        labels: distributionData.map(item => new Date(item.date).toLocaleDateString()),
                        datasets: [{
                                label: 'Distribution Count',
                                data: distributionData.map(item => item.count),
                                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Amount Distributed',
                                data: distributionData.map(item => item.total_amount),
                                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'Amount (₱)'
                                }
                            }
                        }
                    }
                });

                // Location Chart
                const locationCtx = document.getElementById('locationChart').getContext('2d');
                const locationData = @json($locationData);

                const locationChart = new Chart(locationCtx, {
                    type: 'bar',
                    data: {
                        labels: locationData.map(item => item.barangay || 'Unknown'),
                        datasets: [{
                                label: 'Distribution Count',
                                data: locationData.map(item => item.count),
                                backgroundColor: 'rgba(249, 115, 22, 0.6)',
                                borderColor: 'rgba(249, 115, 22, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Households Reached',
                                data: locationData.map(item => item.household_count),
                                backgroundColor: 'rgba(139, 92, 246, 0.6)',
                                borderColor: 'rgba(139, 92, 246, 1)',
                                borderWidth: 1,
                                type: 'line'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        }
                    }
                });
            });
        </script>
    </x-mary-card>

    <!-- Distributions Tabs -->
    <x-mary-card class="mb-6">
        <div>
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px space-x-4">
                    <button wire:click="switchTab('individual')"
                        class="px-3 py-2 text-sm font-medium {{ $distributionTab === 'individual' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Individual Distributions
                    </button>
                    <button wire:click="switchTab('batches')"
                        class="px-3 py-2 text-sm font-medium {{ $distributionTab === 'batches' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Distribution Batches
                    </button>
                </nav>
            </div>

            <div class="mt-4">
                @if ($distributionTab === 'individual')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-base-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Reference #</th>
                                    <th scope="col" class="px-4 py-3">Beneficiary</th>
                                    <th scope="col" class="px-4 py-3">Household</th>
                                    <th scope="col" class="px-4 py-3">Date</th>
                                    <th scope="col" class="px-4 py-3">Amount</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    <th scope="col" class="px-4 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($individualDistributions as $distribution)
                                    <tr class="border-b bg-base hover:bg-base-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $distribution->reference_number }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                                class="text-blue-600 hover:underline">
                                                {{ $distribution->resident->full_name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($distribution->household)
                                                <a href="{{ route('households.show', $distribution->household_id) }}"
                                                    class="text-blue-600 hover:underline">
                                                    {{ $distribution->household->household_id }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $distribution->distribution_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            ₱{{ number_format($distribution->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $distribution->status === 'distributed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : ($distribution->status === 'pending'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($distribution->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <x-mary-button link="{{ route('distributions.show', $distribution->id) }}"
                                                size="xs"
                                                class="tagged-color btn-secondary btn-outline btn-secline">
                                                Details
                                            </x-mary-button>
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($individualDistributions->count() === 0)
                                    <tr class="border-b bg-base">
                                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                            No distributions found for this program
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($individualDistributions->hasPages())
                        <div class="px-4 py-3 border-t">
                            {{ $individualDistributions->links() }}
                        </div>
                    @endif
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-base-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Batch Number</th>
                                    <th scope="col" class="px-4 py-3">Location</th>
                                    <th scope="col" class="px-4 py-3">Date</th>
                                    <th scope="col" class="px-4 py-3">Distributions</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                    <th scope="col" class="px-4 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($distributionBatches as $batch)
                                    <tr class="border-b bg-base hover:bg-base-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $batch->batch_number }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $batch->location }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $batch->batch_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($batch->distributions_count) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $batch->status === 'completed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : ($batch->status === 'ongoing'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : ($batch->status === 'scheduled'
                                                            ? 'bg-yellow-100 text-yellow-800'
                                                            : 'bg-red-100 text-red-800')) }}">
                                                {{ ucfirst($batch->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <x-mary-button
                                                link="{{ route('distributions.batches.show', $batch->id) }}"
                                                size="xs"
                                                class="tagged-color btn-secondary btn-outline btn-secline">
                                                View Batch
                                            </x-mary-button>
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($distributionBatches->count() === 0)
                                    <tr class="border-b bg-base">
                                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                            No distribution batches found for this program
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($distributionBatches->hasPages())
                        <div class="px-4 py-3 border-t">
                            {{ $distributionBatches->links() }}
                        </div>
                    @endif
                @endif

                <div class="flex justify-end mt-4">
                    @if ($distributionTab === 'individual')
                        <x-mary-button link="{{ route('distributions.create') }}?program={{ $program->id }}"
                            class="tagged-color btn-primary" icon="o-banknotes">
                            New Distribution
                        </x-mary-button>
                    @else
                        <x-mary-button link="{{ route('distributions.batches.create') }}?program={{ $program->id }}"
                            class="tagged-color btn-primary" icon="o-users">
                            New Batch
                        </x-mary-button>
                    @endif
                </div>
            </div>
        </div>
    </x-mary-card>
</div>
