<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-base-content/90 leading-tight">
                Mayor Executive Dashboard
            </h2>
            <span class="inline-flex w-fit rounded-full bg-base-200 px-3 py-1 text-xs font-semibold text-base-content/80 badge badge-sm">
                Last 30 Days
            </span>
        </div>
    </x-slot>

    @php
        $generatedLabel = $generatedAt->timezone('Asia/Manila')->format('M d, Y h:i A');
        $totalOverdue = (int) $overdueAcknowledgement + (int) $overdueFirstAction + (int) $overdueResolution;

        $topDepartment = $departmentPerformance->sortByDesc('total_cases')->first();
        $topIssue = $mostSupportedIssues->first();
        $topCategory = $trendingCategories->first();

        $departmentLabels = $departmentPerformance->pluck('department_name')->map(fn ($name) => $name ?: 'Unassigned')->values();
        $departmentCaseData = $departmentPerformance->pluck('total_cases')->map(fn ($v) => (int) $v)->values();
        $departmentResolutionData = $departmentPerformance->pluck('avg_resolution_hours')->map(fn ($v) => round((float) $v, 2))->values();

        $categoryLabels = $trendingCategories->pluck('name')->values();
        $categoryData = $trendingCategories->pluck('total')->map(fn ($v) => (int) $v)->values();
    @endphp

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-sky-900 to-cyan-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-base-100/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-12 h-28 w-28 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Executive View</p>
                        <h3 class="mt-2 text-2xl font-bold sm:text-3xl">City Performance & SLA Signals</h3>
                        <p class="mt-2 max-w-2xl text-sm text-cyan-100/90">
                            Track overdue workload, division speed, and public pressure points to prioritize executive action.
                        </p>
                    </div>

                    <div class="rounded-xl border border-white/20 bg-base-100/10 px-4 py-3 backdrop-blur">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-100">Generated Time</p>
                        <p class="mt-1 text-sm font-semibold text-white">{{ $generatedLabel }}</p>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Overdue Ack</p>
                    <p class="mt-1 text-xl font-bold text-rose-700">{{ number_format((int) $overdueAcknowledgement) }}</p>
                </div>
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Overdue First Action</p>
                    <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format((int) $overdueFirstAction) }}</p>
                </div>
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Overdue Resolution</p>
                    <p class="mt-1 text-xl font-bold text-fuchsia-700">{{ number_format((int) $overdueResolution) }}</p>
                </div>
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Total Overdue</p>
                    <p class="mt-1 text-xl font-bold text-base-content">{{ number_format($totalOverdue) }}</p>
                </div>
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Citizen Satisfaction</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">
                        {{ $citizenSatisfactionIndex !== null ? $citizenSatisfactionIndex.'%' : 'N/A' }}
                    </p>
                </div>
                <div class="rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Top Department</p>
                    <p class="mt-1 truncate text-sm font-bold text-base-content">{{ $topDepartment?->department_name ?? 'N/A' }}</p>
                    <p class="text-xs text-base-content/60">{{ $topDepartment ? number_format((int) $topDepartment->total_cases).' cases' : 'No data' }}</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-base-content">SLA Overdue Composition</h3>
                        <span class="inline-flex rounded-full bg-base-200 px-2.5 py-1 text-[11px] font-semibold text-base-content/70 badge badge-sm">Current Snapshot</span>
                    </div>
                    <div class="mt-3 h-64 sm:h-72">
                        <canvas id="executive-sla-chart"></canvas>
                    </div>
                </article>

                <article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-base-content">Trending Categories</h3>
                        <span class="inline-flex rounded-full bg-base-200 px-2.5 py-1 text-[11px] font-semibold text-base-content/70 badge badge-sm">Last 30 Days</span>
                    </div>
                    <div class="mt-3 h-64 sm:h-72">
                        <canvas id="executive-categories-chart"></canvas>
                    </div>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <article class="xl:col-span-2 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-base-content">Department Throughput & Resolution Speed</h3>
                        <span class="inline-flex rounded-full bg-base-200 px-2.5 py-1 text-[11px] font-semibold text-base-content/70 badge badge-sm">Top 10 Divisions</span>
                    </div>
                    <p class="mt-1 text-xs text-base-content/60">Bars show case volume; line tracks average resolution hours.</p>
                    <div class="mt-3 h-72 sm:h-80">
                        <canvas id="executive-department-chart"></canvas>
                    </div>
                </article>

                <article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
                    <h3 class="text-base font-semibold text-base-content">Public Pressure Signals</h3>
                    <div class="mt-3 space-y-3 text-sm">
                        <div class="rounded-xl border border-base-300 bg-base-200 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Most Supported Issue</p>
                            <p class="mt-1 font-semibold text-base-content">{{ $topIssue?->title ?? 'No data yet' }}</p>
                            <p class="text-xs text-base-content/70">
                                {{ $topIssue ? ('Ref '.$topIssue->reference_code.' | '.number_format((int) $topIssue->support_count).' supports') : '' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-base-300 bg-base-200 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Top Category</p>
                            <p class="mt-1 font-semibold text-base-content">{{ $topCategory?->name ?? 'No data yet' }}</p>
                            <p class="text-xs text-base-content/70">
                                {{ $topCategory ? number_format((int) $topCategory->total).' complaints in 30 days' : '' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <h4 class="text-sm font-semibold text-base-content">Most Supported Issues</h4>
                        @forelse ($mostSupportedIssues->take(5) as $issue)
                            @php
                                $supportPercent = max(6, $topIssue && (int) $topIssue->support_count > 0
                                    ? (int) round(((int) $issue->support_count / (int) $topIssue->support_count) * 100)
                                    : 6);
                            @endphp
                            <div class="rounded-lg border border-base-300 p-2.5">
                                <p class="truncate text-sm font-semibold text-base-content">{{ $issue->title }}</p>
                                <p class="mt-0.5 text-xs text-base-content/60">Supports: {{ number_format((int) $issue->support_count) }}</p>
                                <div class="mt-2 h-1.5 rounded-full bg-base-200">
                                    <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $supportPercent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-base-content/70">No support data available.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @endonce
    <script>
        (() => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const legendPosition = window.innerWidth < 640 ? 'bottom' : 'right';

            const slaEl = document.getElementById('executive-sla-chart');
            if (slaEl) {
                new Chart(slaEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Acknowledgement', 'First Action', 'Resolution'],
                        datasets: [{
                            data: [
                                @json((int) $overdueAcknowledgement),
                                @json((int) $overdueFirstAction),
                                @json((int) $overdueResolution),
                            ],
                            backgroundColor: ['#ef4444', '#f59e0b', '#a855f7'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: legendPosition,
                                labels: {
                                    boxWidth: 12,
                                    boxHeight: 12,
                                },
                            },
                        },
                    },
                });
            }

            const departmentEl = document.getElementById('executive-department-chart');
            if (departmentEl) {
                new Chart(departmentEl, {
                    type: 'bar',
                    data: {
                        labels: @json($departmentLabels),
                        datasets: [{
                            label: 'Cases',
                            data: @json($departmentCaseData),
                            backgroundColor: '#0ea5e9',
                            borderRadius: 8,
                            yAxisID: 'yCases',
                        }, {
                            type: 'line',
                            label: 'Avg Resolution (hrs)',
                            data: @json($departmentResolutionData),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            pointBackgroundColor: '#10b981',
                            pointRadius: 3,
                            tension: 0.25,
                            yAxisID: 'yHours',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            yCases: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Cases',
                                },
                                ticks: {
                                    precision: 0,
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
                                },
                            },
                            yHours: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Resolution Hours',
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                            },
                            x: {
                                grid: {
                                    display: false,
                                },
                            },
                        },
                    },
                });
            }

            const categoriesEl = document.getElementById('executive-categories-chart');
            if (categoriesEl) {
                new Chart(categoriesEl, {
                    type: 'bar',
                    data: {
                        labels: @json($categoryLabels),
                        datasets: [{
                            label: 'Complaints',
                            data: @json($categoryData),
                            backgroundColor: '#3b82f6',
                            borderRadius: 8,
                            maxBarThickness: 42,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
                                },
                            },
                            y: {
                                grid: {
                                    display: false,
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                        },
                    },
                });
            }
        })();
    </script>
</x-app-layout>
