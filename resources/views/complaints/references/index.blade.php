<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-base-content/90">BosesMoto & Citizen Services References</h2>
            <span class="rounded-full bg-base-200 px-3 py-1 text-xs font-semibold text-base-content/80 badge badge-sm">Reference Data</span>
        </div>
    </x-slot>

    <div class="space-y-6 py-6">
        @include('complaints.references._nav')

        <section class="rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-800 p-6 text-white shadow-lg">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Administration</p>
            <h1 class="mt-2 text-2xl font-bold">Reference Data Management</h1>
            <p class="mt-2 max-w-3xl text-sm text-cyan-50/90">
                Maintain the shared lookup records used for complaint intake, routing, public reporting, officer assignment, SOS dispatch, and citizen portal links.
            </p>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['route' => 'complaints.categories.index', 'title' => 'Complaint Categories', 'description' => 'Issue taxonomy used during complaint submission.', 'count' => $counts['categories'], 'badge' => 'bg-rose-100 text-rose-700'],
                ['route' => 'complaints.barangays.index', 'title' => 'BosesMoto Barangays', 'description' => 'Location choices used by complaint forms.', 'count' => $counts['barangays'], 'badge' => 'bg-emerald-100 text-emerald-700'],
                ['route' => 'complaints.departments.index', 'title' => 'Departments', 'description' => 'Internal routing units and contact details.', 'count' => $counts['departments'], 'badge' => 'bg-cyan-100 text-cyan-700'],
                ['route' => 'complaints.action-officers.index', 'title' => 'Action Officers', 'description' => 'Responder accounts and department assignments.', 'count' => $counts['action_officers'], 'badge' => 'bg-amber-100 text-amber-700'],
                ['route' => 'complaints.officials.index', 'title' => 'Public Officials', 'description' => 'Officials that can be tagged on complaints.', 'count' => $counts['officials'], 'badge' => 'bg-indigo-100 text-indigo-700'],
                ['route' => 'complaints.sos-departments.index', 'title' => 'SOS Departments', 'description' => 'Emergency response units, codes, and hotlines.', 'count' => $counts['sos_departments'], 'badge' => 'bg-red-100 text-red-700'],
            ] as $item)
                <a href="{{ route($item['route']) }}" class="group rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-base-content group-hover:text-blue-700">{{ $item['title'] }}</h2>
                            <p class="mt-2 text-sm text-base-content/70">{{ $item['description'] }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $item['badge'] }}">{{ number_format($item['count']) }}</span>
                    </div>
                </a>
            @endforeach

            <a href="{{ route('citizen-services.index', ['activeTab' => 'links']) }}" class="group rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-blue-900">Public Service Links</h2>
                        <p class="mt-2 text-sm text-blue-700">Citizen portal destinations and service types.</p>
                    </div>
                    <span class="rounded-full bg-blue-200 px-3 py-1 text-sm font-bold text-blue-800">{{ number_format($counts['portal_links']) }}</span>
                </div>
            </a>
        </section>
    </div>
</x-app-layout>
