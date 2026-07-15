@foreach ($config['fields'] as $field => $definition)
    <fieldset class="fieldset {{ $definition['type'] === 'checkbox' ? 'justify-end' : '' }}">
        @if ($definition['type'] === 'checkbox')
            <label class="label mt-6 cursor-pointer justify-start gap-3">
                <input type="checkbox" wire:model="form.{{ $field }}" class="checkbox checkbox-primary">
                <span class="label-text">{{ $definition['label'] }}</span>
            </label>
        @else
            <legend class="fieldset-legend">{{ $definition['label'] }}</legend>
            @if ($definition['type'] === 'select')
                <select wire:model="form.{{ $field }}" class="select select-bordered w-full">
                    @foreach ($definition['options'] as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            @elseif ($definition['type'] === 'barangay')
                <select wire:model="form.{{ $field }}" class="select select-bordered w-full">
                    <option value="">Unmapped</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay['value'] }}">{{ $barangay['label'] }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" wire:model="form.{{ $field }}" class="input input-bordered w-full">
            @endif
        @endif
        @error('form.'.$field)
            <p class="text-error mt-1 text-sm">{{ $message }}</p>
        @enderror
    </fieldset>
@endforeach
