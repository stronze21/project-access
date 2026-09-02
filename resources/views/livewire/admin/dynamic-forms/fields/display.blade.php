@php
    $type = $field['type'] ?? 'short_text';
    $key = $field['key'];
    $label = $field['label'] ?? $key;
    $value = $answers[$key] ?? null;
@endphp

<div class="rounded-lg border border-slate-200 p-3">
    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
    <div class="mt-1 text-sm text-slate-800">
        @if ($value === null || $value === '' || $value === [])
            <span class="text-slate-400">No answer</span>
        @elseif ($type === 'file' && is_array($value))
            <a class="link" href="{{ route('forms.submissions.file', [$submission, $key]) }}">{{ $value['name'] ?? 'Download file' }}</a>
        @elseif ($type === 'resident')
            @php $resident = \App\Models\Resident::find($value); @endphp
            {{ $resident ? $resident->full_name.' ('.$resident->resident_id.')' : $value }}
        @elseif (is_array($value))
            {{ implode(', ', array_map('strval', $value)) }}
        @else
            {{ $value }}
        @endif
    </div>
</div>
