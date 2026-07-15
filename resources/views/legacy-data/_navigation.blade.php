@php
    $legacyDataPages = [
        'source-income-types' => 'Income Types',
        'educational-attainments' => 'Education',
        'civil-statuses' => 'Civil Status',
        'barangays' => 'Barangays',
    ];
@endphp

<nav class="flex flex-wrap gap-2" aria-label="Legacy data management">
    @foreach ($legacyDataPages as $page => $label)
        <a href="{{ route('legacy-data.references.index', $page) }}"
            class="btn btn-sm {{ ($type ?? null) === $page ? 'btn-primary' : 'btn-ghost' }}">
            {{ $label }}
        </a>
    @endforeach
    <a href="{{ route('legacy-data.bhw.index') }}"
        class="btn btn-sm {{ ($type ?? null) === 'bhw' ? 'btn-primary' : 'btn-ghost' }}">
        BHW Master
    </a>
</nav>
