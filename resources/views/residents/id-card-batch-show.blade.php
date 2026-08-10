<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div><h2 class="text-xl font-semibold text-gray-800">Print Batch Details</h2><p class="font-mono text-xs text-gray-500">{{ $printBatch->reference_number }}</p></div>
            <x-mary-button link="{{ route('residents.id-cards.batches.index') }}" icon="o-arrow-left" class="btn-outline">Print History</x-mary-button>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-5 sm:px-6 lg:px-8">
            <x-mary-card>
                <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div><dt class="text-xs text-gray-500">Scope</dt><dd class="font-semibold">{{ $printBatch->barangay === 'all' ? 'All Barangays' : ($printBatch->barangay ?: 'Manual selection') }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Batch</dt><dd class="font-semibold">{{ $printBatch->batch_number }}</dd></div>
                    <div><dt class="text-xs text-gray-500">IDs</dt><dd class="font-semibold">{{ $printBatch->resident_count }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Status</dt><dd class="font-semibold">{{ str($printBatch->status)->replace('_', ' ')->headline() }}</dd></div>
                    <div><dt class="text-xs text-gray-500">User</dt><dd>{{ $printBatch->user?->name ?: 'Unknown user' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Generated</dt><dd>{{ $printBatch->created_at->format('M d, Y h:i A') }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Print initiated</dt><dd>{{ $printBatch->printed_at?->format('M d, Y h:i A') ?: 'Not yet' }}</dd></div>
                </dl>
            </x-mary-card>

            <x-mary-card title="Residents in this batch">
                <div class="overflow-x-auto"><table class="table"><thead><tr><th>#</th><th>Resident ID</th><th>Name at generation</th><th>Print status</th></tr></thead><tbody>
                    @foreach ($printBatch->items as $item)
                        <tr><td>{{ $loop->iteration }}</td><td class="font-mono">{{ $item->resident_pin }}</td><td>{{ $item->resident_name }}</td><td>{{ $item->printed_at ? 'Print initiated '.$item->printed_at->format('M d, Y h:i A') : 'Not printed' }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
