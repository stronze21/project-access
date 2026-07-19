<div class="p-4">
    <div class="flex items-start justify-between gap-2">
        <p class="break-all text-xs font-medium text-gray-700">{{ $result['file_name'] }}</p>
        @if ($result['imported'])
            <x-mary-badge value="Imported" class="badge-success badge-sm" />
        @elseif ($result['valid'])
            <x-mary-badge value="Valid" class="badge-info badge-sm" />
        @else
            <x-mary-badge value="Invalid" class="badge-error badge-sm" />
        @endif
    </div>
    <progress class="progress {{ $result['valid'] ? 'progress-success' : 'progress-error' }} mt-3 w-full" value="100" max="100"></progress>
    @if ($result['resident_name'])
        <p class="mt-3 text-sm font-semibold text-gray-900">{{ $result['resident_name'] }}</p>
        <p class="text-xs text-gray-500">{{ $result['resident_id'] }}</p>
    @endif
    <p class="mt-2 text-xs {{ $result['valid'] ? 'text-green-700' : 'text-red-700' }}">{{ $result['message'] }}</p>
</div>
