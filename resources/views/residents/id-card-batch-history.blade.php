<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Resident ID Print History</h2>
            <x-mary-button link="{{ route('residents.id-cards.form') }}" icon="o-plus" class="btn-primary">New Print Batch</x-mary-button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            <x-mary-card>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Reference</th><th>Scope</th><th>Batch</th><th>IDs</th><th>Status</th><th>Prepared/Printed By</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($printBatches as $batch)
                                <tr>
                                    <td class="font-mono text-xs">{{ $batch->reference_number }}</td>
                                    <td>{{ $batch->barangay === 'all' ? 'All Barangays' : ($batch->barangay ?: 'Manual selection') }}<br><span class="text-xs text-gray-500">{{ str($batch->status_filter)->headline() }}</span></td>
                                    <td>{{ $batch->batch_number }}</td>
                                    <td>{{ $batch->resident_count }}</td>
                                    <td><x-mary-badge :value="$batch->status === 'print_initiated' ? 'Print initiated' : 'Generated'" :class="$batch->status === 'print_initiated' ? 'badge-success' : 'badge-warning'" /></td>
                                    <td>{{ $batch->user?->name ?: 'Unknown user' }}</td>
                                    <td>{{ ($batch->printed_at ?: $batch->created_at)->format('M d, Y h:i A') }}</td>
                                    <td><x-mary-button link="{{ route('residents.id-cards.batches.show', $batch) }}" size="sm" class="btn-outline">View IDs</x-mary-button></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-8 text-center text-gray-500">No ID print batches have been generated yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $printBatches->links() }}</div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
