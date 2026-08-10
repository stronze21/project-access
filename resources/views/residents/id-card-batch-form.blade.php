<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Generate Batch ACCESS ID Cards</h2>
            <div class="flex gap-2">
                <x-mary-button link="{{ route('residents.id-cards.batches.index') }}"
                    class="btn-secondary btn-outline" icon="o-clock">Print History</x-mary-button>
                <x-mary-button link="{{ route('residents.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left">Back to Residents</x-mary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-xl font-semibold">Print IDs by Barangay</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Landscape CR80 format. Each print batch contains up to {{ \App\Http\Controllers\ResidentIdCardController::MAX_BATCH_SIZE }} residents in alphabetical order.
                </p>

                <form action="{{ route('residents.id-cards.batch') }}" method="POST" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <x-mary-select name="barangay" id="filter-barangay" label="Barangay" required
                            placeholder="Select a barangay" :options="collect([['key' => 'all', 'id' => 'All Barangays']])->concat(collect($barangayList)->map(fn ($barangay) => ['key' => $barangay, 'id' => $barangay]))->values()->toArray()"
                            option-value="key" option-label="id" :value="old('barangay', $selectedBarangay)" />

                        <x-mary-select name="status" id="filter-status" label="Resident Status" :options="[
                            ['key' => 'active', 'id' => 'Active Residents'],
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'inactive', 'id' => 'Inactive Residents'],
                        ]" option-value="key" option-label="id" :value="old('status', $selectedStatus)" />

                    </div>

                    <input type="hidden" name="exclude_printed" value="0">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                        <input type="checkbox" name="exclude_printed" value="1"
                            class="checkbox checkbox-primary mt-0.5"
                            @checked(old('exclude_printed', $excludePrinted))>
                        <span>
                            <strong class="block text-sm text-gray-900">Hide residents already assigned or printed</strong>
                            <span class="mt-1 block text-xs text-gray-500">Recommended to prevent duplicate ID printing. Uncheck only when an intentional reprint is required.</span>
                        </span>
                    </label>

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <strong class="block">The print batch could not be generated</strong>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        With duplicate protection enabled, residents already assigned to a tracked batch remain there. New residents are added to the latest unprinted batch when space is available, or placed in a new batch automatically. Printed batches are never changed.
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <x-mary-button type="submit" class="tagged-color btn-primary" icon="o-printer">
                            Generate Barangay Batch
                        </x-mary-button>
                    </div>
                </form>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
