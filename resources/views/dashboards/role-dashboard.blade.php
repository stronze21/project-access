<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $title }}
            </h2>
            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full w-fit bg-slate-100 text-slate-700">
                {{ auth()->user()->role }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 space-y-5 max-w-7xl-removed mx-auto sm:px-6 lg:px-8">
            <section class="relative p-5 overflow-hidden text-white shadow-lg rounded-2xl bg-gradient-to-br from-slate-900 via-cyan-900 to-blue-900 sm:p-6">
                <div class="absolute rounded-full pointer-events-none -right-8 -top-8 h-28 w-28 bg-white/10 blur-2xl"></div>
                <div class="absolute w-24 h-24 rounded-full pointer-events-none -bottom-8 left-20 bg-cyan-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Operations Overview</p>
                    <h3 class="mt-2 text-xl font-bold sm:text-2xl">{{ $title }}</h3>
                    <p class="max-w-2xl mt-2 text-sm text-cyan-100/90">
                        Welcome, {{ auth()->user()->name }}. {{ $subtitle }}
                    </p>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($stats as $stat)
                    @php
                        $accentClass = match ($loop->index) {
                            0 => 'text-slate-900',
                            1 => 'text-blue-700',
                            2 => 'text-emerald-700',
                            3 => 'text-cyan-700',
                            4 => 'text-rose-700',
                            default => 'text-indigo-700',
                        };
                    @endphp

                    <div class="p-3 bg-white shadow-sm rounded-xl ring-1 ring-slate-100 sm:p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-lg font-bold sm:text-2xl {{ $accentClass }}">{{ number_format((int) $stat['value']) }}</p>
                    </div>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Complaint Status Distribution</h3>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Current Scope</span>
                    </div>
                    <div class="h-64 mt-3 sm:h-72">
                        <canvas id="dashboard-status-chart"></canvas>
                    </div>
                </div>

                <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">6-Month Submission Trend</h3>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Monthly</span>
                    </div>
                    <div class="h-64 mt-3 sm:h-72">
                        <canvas id="dashboard-trend-chart"></canvas>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Response Time Trend (Hours)</h3>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Last 6 Months</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Computed from submission to first action, and submission to resolved/closed.</p>
                    <div class="h-64 mt-3 sm:h-72">
                        <canvas id="dashboard-response-trend-chart"></canvas>
                    </div>
                </div>

                <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Average Response Snapshot (Hours)</h3>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Current Scope</span>
                    </div>
                    <div class="h-64 mt-3 sm:h-72">
                        <canvas id="dashboard-response-snapshot-chart"></canvas>
                    </div>
                </div>
            </section>

            <section class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Division Response Time Per Request</h3>
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Top 10 Divisions</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">Bars show average hours per request. Line shows request count per division.</p>
                <div class="mt-3 h-72 sm:h-80">
                    <canvas id="dashboard-division-response-chart"></canvas>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="p-4 bg-white border shadow-sm xl:col-span-2 rounded-2xl border-slate-200 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Priority Breakdown</h3>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Current Scope</span>
                    </div>
                    <div class="h-64 mt-3 sm:h-72">
                        <canvas id="dashboard-priority-chart"></canvas>
                    </div>
                </div>

                <div class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900">Suggested Graph Focus</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-700">
                        @foreach ($graphSuggestions as $suggestion)
                            <li class="px-3 py-2 rounded-lg bg-slate-50">
                                {{ $suggestion }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            @if (!empty($cards))
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cards as $card)
                        @php
                            $cardHref = $card['href'] ?? null;
                            $isClickableCard = is_string($cardHref) && $cardHref !== '';
                        @endphp

                        @if ($isClickableCard)
                            <a href="{{ $cardHref }}"
                               class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md sm:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-blue-700">{{ $card['title'] }}</h3>
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700">
                                        Open
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ $card['description'] }}</p>
                            </a>
                        @else
                            <article class="p-4 bg-white border shadow-sm rounded-2xl border-slate-200 sm:p-5">
                                <h3 class="text-base font-semibold text-slate-900">{{ $card['title'] }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $card['description'] }}</p>
                            </article>
                        @endif
                    @endforeach
                </section>
            @endif
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

            const statusConfig = @json($charts['status']);
            const trendConfig = @json($charts['trend']);
            const priorityConfig = @json($charts['priority']);
            const responseTrendConfig = @json($charts['responseTrend']);
            const responseSnapshotConfig = @json($charts['responseSnapshot']);
            const divisionResponseConfig = @json($charts['divisionResponse']);
            const legendPosition = window.innerWidth < 640 ? 'bottom' : 'right';

            const statusEl = document.getElementById('dashboard-status-chart');
            if (statusEl) {
                new Chart(statusEl, {
                    type: 'doughnut',
                    data: {
                        labels: statusConfig.labels,
                        datasets: [{
                            data: statusConfig.data,
                            backgroundColor: ['#2563eb', '#0ea5e9', '#f59e0b', '#10b981', '#64748b'],
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

            const trendEl = document.getElementById('dashboard-trend-chart');
            if (trendEl) {
                new Chart(trendEl, {
                    type: 'line',
                    data: {
                        labels: trendConfig.labels,
                        datasets: [{
                            label: 'Complaints',
                            data: trendConfig.data,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
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

            const priorityEl = document.getElementById('dashboard-priority-chart');
            if (priorityEl) {
                new Chart(priorityEl, {
                    type: 'bar',
                    data: {
                        labels: priorityConfig.labels,
                        datasets: [{
                            label: 'Cases',
                            data: priorityConfig.data,
                            backgroundColor: ['#60a5fa', '#34d399', '#fbbf24', '#f87171'],
                            borderRadius: 8,
                            maxBarThickness: 48,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
                                },
                            },
                            x: {
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

            const responseTrendEl = document.getElementById('dashboard-response-trend-chart');
            if (responseTrendEl) {
                new Chart(responseTrendEl, {
                    type: 'line',
                    data: {
                        labels: responseTrendConfig.labels,
                        datasets: [{
                            label: 'Avg First Response (hrs)',
                            data: responseTrendConfig.first_action_hours,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.15)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                        }, {
                            label: 'Avg Resolution (hrs)',
                            data: responseTrendConfig.resolution_hours,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.15)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours',
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
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

            const responseSnapshotEl = document.getElementById('dashboard-response-snapshot-chart');
            if (responseSnapshotEl) {
                new Chart(responseSnapshotEl, {
                    type: 'bar',
                    data: {
                        labels: responseSnapshotConfig.labels,
                        datasets: [{
                            label: 'Hours',
                            data: responseSnapshotConfig.data,
                            backgroundColor: ['#0ea5e9', '#10b981'],
                            borderRadius: 10,
                            maxBarThickness: 64,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours',
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
                                },
                            },
                            x: {
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

            const divisionResponseEl = document.getElementById('dashboard-division-response-chart');
            if (divisionResponseEl) {
                new Chart(divisionResponseEl, {
                    type: 'bar',
                    data: {
                        labels: divisionResponseConfig.labels,
                        datasets: [{
                            label: 'Avg First Response (hrs/request)',
                            data: divisionResponseConfig.first_action_hours,
                            backgroundColor: '#0ea5e9',
                            borderRadius: 8,
                            yAxisID: 'yHours',
                        }, {
                            label: 'Avg Resolution (hrs/request)',
                            data: divisionResponseConfig.resolution_hours,
                            backgroundColor: '#10b981',
                            borderRadius: 8,
                            yAxisID: 'yHours',
                        }, {
                            type: 'line',
                            label: 'Requests',
                            data: divisionResponseConfig.request_counts,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.2)',
                            pointBackgroundColor: '#f59e0b',
                            pointRadius: 3,
                            tension: 0.25,
                            yAxisID: 'yRequests',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            yHours: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours / Request',
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.25)',
                                },
                            },
                            yRequests: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Requests',
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    precision: 0,
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
        })();
    </script>
</x-app-layout>
