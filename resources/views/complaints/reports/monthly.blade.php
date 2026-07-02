<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Internal Monthly Complaint Report
            </h2>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Management Report
            </span>
        </div>
    </x-slot>

    @php
        $selectedMonth = request('month', $start->format('Y-m'));
        $totalComplaints = (int) $byStatus->sum('total');
        $openCount = (int) $byStatus->filter(fn ($row) => in_array($row->status, [
            \App\Models\Complaint::STATUS_RECEIVED,
            \App\Models\Complaint::STATUS_ASSIGNED,
            \App\Models\Complaint::STATUS_IN_PROGRESS,
        ], true))->sum('total');
        $resolvedCount = (int) data_get($byStatus->firstWhere('status', \App\Models\Complaint::STATUS_RESOLVED), 'total', 0);
        $closedCount = (int) data_get($byStatus->firstWhere('status', \App\Models\Complaint::STATUS_CLOSED), 'total', 0);
        $maxStatusTotal = max(1, (int) $byStatus->max('total'));
        $maxDepartmentTotal = max(1, (int) $byDepartment->max('total'));
        $maxCategoryTotal = max(1, (int) $byCategory->max('total'));
        $topDepartment = $byDepartment->first();
        $topCategory = $byCategory->first();
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-teal-900 to-emerald-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-emerald-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Analytics</p>
                    <h3 class="mt-2 text-xl font-bold sm:text-2xl">Monthly Complaint Performance Snapshot</h3>
                    <p class="mt-2 max-w-3xl text-sm text-emerald-100/90">
                        Reporting period: {{ $start->format('F 1, Y') }} to {{ $end->format('F d, Y') }}.
                        Average resolution time: {{ $avgResolutionHours !== null ? number_format((float) $avgResolutionHours, 2).' hours' : 'N/A' }}.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Report Month</h3>
                        <p class="text-xs text-slate-500">Generate a monthly internal performance report.</p>
                    </div>
                </div>

                <form method="GET" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="month" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Month</label>
                        <input id="month"
                               type="month"
                               name="month"
                               value="{{ $selectedMonth }}"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Generate
                        </button>
                        <a href="{{ route('complaints.reports.monthly') }}"
                           class="inline-flex rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Cases</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($totalComplaints) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Open</p>
                    <p class="mt-1 text-xl font-bold text-blue-700">{{ number_format($openCount) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Resolved</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($resolvedCount) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Closed</p>
                    <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($closedCount) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Top Department</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $topDepartment?->department_name ?? 'Unassigned' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Top Category</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $topCategory?->category_name ?? 'N/A' }}</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">By Status</h3>
                    <div class="mt-3 space-y-3">
                        @forelse ($byStatus as $row)
                            @php
                                $statusPercent = min(100, (int) round(((int) $row->total / $maxStatusTotal) * 100));
                            @endphp

                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ str_replace('_', ' ', ucfirst($row->status)) }}</p>
                                    <p class="text-sm font-bold text-slate-700">{{ number_format((int) $row->total) }}</p>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-blue-500" style="width: {{ $statusPercent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">No status data.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">By Department</h3>
                    <div class="mt-3 space-y-3">
                        @forelse ($byDepartment as $row)
                            @php
                                $departmentPercent = min(100, (int) round(((int) $row->total / $maxDepartmentTotal) * 100));
                            @endphp

                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $row->department_name ?? 'Unassigned' }}</p>
                                    <p class="text-sm font-bold text-slate-700">{{ number_format((int) $row->total) }}</p>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-cyan-500" style="width: {{ $departmentPercent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">No department data.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">By Category</h3>
                    <div class="mt-3 space-y-3">
                        @forelse ($byCategory as $row)
                            @php
                                $categoryPercent = min(100, (int) round(((int) $row->total / $maxCategoryTotal) * 100));
                            @endphp

                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $row->category_name }}</p>
                                    <p class="text-sm font-bold text-slate-700">{{ number_format((int) $row->total) }}</p>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $categoryPercent }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-600">No category data.</div>
                        @endforelse
                    </div>
                </section>
            </section>
        </div>
    </div>
</x-app-layout>
