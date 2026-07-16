<nav class="tabs tabs-boxed flex-wrap bg-base-200 p-2" aria-label="Reference data navigation">
    <div class="contents">
        @foreach ([
            ['route' => 'complaints.references.index', 'label' => 'Overview', 'pattern' => 'complaints.references.*'],
            ['route' => 'complaints.categories.index', 'label' => 'Categories', 'pattern' => 'complaints.categories.*'],
            ['route' => 'complaints.barangays.index', 'label' => 'Barangays', 'pattern' => 'complaints.barangays.*'],
            ['route' => 'complaints.departments.index', 'label' => 'Departments', 'pattern' => 'complaints.departments.*'],
            ['route' => 'complaints.action-officers.index', 'label' => 'Action Officers', 'pattern' => 'complaints.action-officers.*'],
            ['route' => 'complaints.officials.index', 'label' => 'Public Officials', 'pattern' => 'complaints.officials.*'],
            ['route' => 'complaints.sos-departments.index', 'label' => 'SOS Departments', 'pattern' => 'complaints.sos-departments.*'],
        ] as $item)
            <a href="{{ route($item['route']) }}"
               class="tab h-auto min-h-9 py-2 text-xs font-semibold {{ request()->routeIs($item['pattern']) ? 'tab-active' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
        <a href="{{ route('citizen-services.index', ['activeTab' => 'links']) }}"
           class="tab h-auto min-h-9 py-2 text-xs font-semibold text-primary">
            Portal Links
        </a>
    </div>
</nav>
