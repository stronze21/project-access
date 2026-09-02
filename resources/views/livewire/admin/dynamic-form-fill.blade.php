<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('forms.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Forms</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $form->title }}</h1>
            @if ($form->description)
                <p class="mt-1 text-sm text-gray-600">{{ $form->description }}</p>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($form->link_to_resident)
        <x-mary-card class="mb-6" title="Linked resident">
            @if ($selectedResidentLabel)
                <div class="flex items-center justify-between gap-3">
                    <p class="font-medium text-slate-800">{{ $selectedResidentLabel }}</p>
                    <x-mary-button class="btn-ghost btn-sm" wire:click="clearResident">Change</x-mary-button>
                </div>
            @else
                <x-mary-input label="Search resident" wire:model.live.debounce.300ms="residentSearch" placeholder="Name or PIN" />
                @if ($residentResults)
                    <div class="mt-2 divide-y rounded-lg border border-slate-200">
                        @foreach ($residentResults as $result)
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" wire:click="selectResident({{ $result['id'] }}, @js($result['label']))">
                                {{ $result['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
            @error('resident_id')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-mary-card>
    @endif

    <x-mary-card>
        <div class="space-y-5">
            @foreach ($fields as $field)
                @include('livewire.admin.dynamic-forms.fields.input', ['field' => $field, 'existing' => $existingAnswers])
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-2">
            <button type="button" class="btn btn-outline btn-sm h-9 min-h-9" wire:click="save">Save draft</button>
            <button type="button" class="btn btn-primary btn-sm h-9 min-h-9" wire:click="submit">Submit</button>
        </div>
    </x-mary-card>
</div>
