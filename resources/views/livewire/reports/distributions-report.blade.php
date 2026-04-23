<div class="container p-4 mx-auto">
    <h1 class="mb-4 text-2xl font-bold">Distributions Report</h1>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 mb-6">
        <select wire:model.live="dateRange" class="w-full max-w-xs select select-bordered">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="this_week">This Week</option>
            <option value="last_week">Last Week</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_year">This Year</option>
            <option value="custom">Custom Range</option>
        </select>

        <div class="flex gap-2" x-show="dateRange === 'custom'">
            <input type="date" wire:model.live="customStartDate" class="input input-bordered" />
            <input type="date" wire:model.live="customEndDate" class="input input-bordered" />
        </div>

        <select wire:model.live="programFilter" class="w-full max-w-xs select select-bordered">
            <option value="all">All Programs</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}">{{ $program->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="w-full max-w-xs select select-bordered">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="distributed">Distributed</option>
        </select>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
        <div class="p-4 shadow-md card bg-base-100">
            <h2 class="text-lg font-semibold">Total Distributions</h2>
            <p class="text-xl font-bold">{{ $summaryStats->total_distributions }}</p>
        </div>
        <div class="p-4 shadow-md card bg-base-100">
            <h2 class="text-lg font-semibold">Completed Distributions</h2>
            <p class="text-xl font-bold">{{ $summaryStats->completed_distributions }}</p>
        </div>
        <div class="p-4 shadow-md card bg-base-100">
            <h2 class="text-lg font-semibold">Total Amount Distributed</h2>
            <p class="text-xl font-bold">₱{{ number_format($summaryStats->total_amount, 2) }}</p>
        </div>
    </div>

    <!-- Distribution Table -->
    <x-mary-card class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Program</th>
                    <th>Resident</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentDistributions as $distribution)
                    <tr>
                        <td>{{ $distribution->distribution_date }}</td>
                        <td>{{ $distribution->ayudaProgram->name ?? 'N/A' }}</td>
                        <td>{{ $distribution->resident->full_name ?? 'N/A' }}</td>
                        <td>
                            <span
                                class="badge {{ $distribution->status == 'distributed' ? 'badge-success' : 'badge-warning' }}">
                                {{ ucfirst($distribution->status) }}
                            </span>
                        </td>
                        <td>₱{{ number_format($distribution->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-mary-card>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $recentDistributions->links() }}
    </div>
</div>
