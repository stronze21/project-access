@php
    $type = $field->type ?? $field['type'];
    $key = $field->key ?? $field['key'];
    $label = $field->label ?? $field['label'];
    $help = $field->help_text ?? $field['help_text'] ?? null;
    $options = method_exists($field, 'normalizedOptions') ? $field->normalizedOptions() : ($field['options'] ?? []);
    $model = $model ?? "answers.{$key}";
@endphp

<div class="space-y-1">
    <label class="text-sm font-medium text-slate-800">
        {{ $label }}
        @if ($field->is_required ?? $field['is_required'] ?? false)
            <span class="text-rose-600">*</span>
        @endif
    </label>
    @if ($help)
        <p class="text-xs text-slate-500">{{ $help }}</p>
    @endif

    @switch($type)
        @case('long_text')
            <textarea class="textarea textarea-bordered w-full" rows="4" wire:model="{{ $model }}"></textarea>
            @break
        @case('number')
            <input type="number" step="any" class="input input-bordered w-full" wire:model="{{ $model }}" />
            @break
        @case('date')
            <input type="date" class="input input-bordered w-full" wire:model="{{ $model }}" />
            @break
        @case('dropdown')
            <select class="select select-bordered w-full" wire:model="{{ $model }}">
                <option value="">Select…</option>
                @foreach ($options as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            @break
        @case('radio')
            <div class="space-y-2">
                @foreach ($options as $option)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" class="radio radio-sm" wire:model="{{ $model }}" value="{{ $option['value'] }}" />
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @break
        @case('checkboxes')
            <div class="space-y-2">
                @foreach ($options as $option)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="checkbox checkbox-sm" wire:model="{{ $model }}" value="{{ $option['value'] }}" />
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @break
        @case('yes_no')
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" class="radio radio-sm" wire:model="{{ $model }}" value="yes" /> Yes
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" class="radio radio-sm" wire:model="{{ $model }}" value="no" /> No
                </label>
            </div>
            @break
        @case('file')
            <input type="file" class="file-input file-input-bordered w-full" wire:model="uploads.{{ $key }}" />
            @if (! empty($existing[$key]['name']))
                <p class="text-xs text-slate-500">Current file: {{ $existing[$key]['name'] }}</p>
            @endif
            @break
        @case('resident')
            <input type="number" class="input input-bordered w-full" wire:model="{{ $model }}" placeholder="Resident record ID" />
            <p class="text-xs text-slate-500">Enter the resident database ID, or use the resident picker above when the form is linked to a resident.</p>
            @break
        @default
            <input type="text" class="input input-bordered w-full" wire:model="{{ $model }}" />
    @endswitch

    @error("answers.{$key}")
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error("uploads.{$key}")
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
