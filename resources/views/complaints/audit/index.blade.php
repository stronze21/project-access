<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Complaint Audit Logs
            </h2>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Compliance Trail
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-emerald-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Monitoring</p>
                    <h3 class="mt-2 text-xl font-bold sm:text-2xl">Immutable Complaint Activity Timeline</h3>
                    <p class="mt-2 max-w-2xl text-sm text-emerald-100/90">
                        Review assignments, status changes, moderation actions, and attachment access events across all cases.
                    </p>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Logs</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) ($stats['total'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Today</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format((int) ($stats['today'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Event Types</p>
                    <p class="mt-1 text-xl font-bold text-blue-700">{{ number_format((int) ($stats['event_types'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Actors</p>
                    <p class="mt-1 text-xl font-bold text-indigo-700">{{ number_format((int) ($stats['actors'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Linked to Case</p>
                    <p class="mt-1 text-xl font-bold text-cyan-700">{{ number_format((int) ($stats['with_complaint'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Filtered Result</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) ($stats['filtered'] ?? 0)) }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Filters</h3>
                        <p class="text-xs text-slate-500">Search events by activity, complaint reference, actor, and date window.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('complaints.audit.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Clear Filters
                        </a>
                    @endif
                </div>

                <form method="GET" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <div class="xl:col-span-2">
                        <label for="q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="q"
                               name="q"
                               value="{{ request('q') }}"
                               type="text"
                               placeholder="Event, actor, role, title"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="event_type" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Event Type</label>
                        <select id="event_type" name="event_type" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All events</option>
                            @foreach ($eventTypes as $eventType)
                                <option value="{{ $eventType }}" @selected(request('event_type') === $eventType)>{{ ucwords(str_replace('_', ' ', $eventType)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reference_code" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</label>
                        <input id="reference_code"
                               name="reference_code"
                               value="{{ request('reference_code') }}"
                               type="text"
                               placeholder="CMP-2026-0001"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="date_from" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                        <input id="date_from"
                               name="date_from"
                               value="{{ request('date_from') }}"
                               type="date"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="date_to" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                        <input id="date_to"
                               name="date_to"
                               value="{{ request('date_to') }}"
                               type="date"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="xl:col-span-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            Apply Filters
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($activeFilterLabels as $label)
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-4 md:hidden">
                @forelse ($logs as $log)
                    @php
                        $eventValue = strtolower($log->event_type);
                        $eventClass = match (true) {
                            str_contains($eventValue, 'delete'), str_contains($eventValue, 'remove'), str_contains($eventValue, 'spam'), str_contains($eventValue, 'abusive') => 'bg-rose-100 text-rose-700',
                            str_contains($eventValue, 'resolve'), str_contains($eventValue, 'close') => 'bg-emerald-100 text-emerald-700',
                            str_contains($eventValue, 'assign'), str_contains($eventValue, 'status'), str_contains($eventValue, 'update') => 'bg-blue-100 text-blue-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-500">{{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $log->complaint?->reference_code ?? 'No complaint link' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $eventClass }}">
                                {{ $log->eventTypeLabel() }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-slate-600">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Actor</p>
                                <p class="mt-0.5 text-slate-700">{{ $log->actor?->name ?? 'System' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $log->actor?->role ?? 'Automated' }}</p>
                            </div>
                        </div>

                        @if ($log->complaint?->title)
                            <p class="mt-3 text-xs text-slate-600">
                                {{ $log->complaint->title }}
                            </p>
                        @endif

                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <p class="text-xs font-semibold text-slate-700">User Activity</p>
                            <div class="mt-2 space-y-1 text-[11px] text-slate-600">
                                @if (count($log->userActivityLines()) > 0)
                                    @foreach ($log->userActivityLines() as $line)
                                        <p>{{ $line }}</p>
                                    @endforeach
                                @else
                                    <p>No additional user activity details.</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">
                        No audit logs found.
                    </div>
                @endforelse
            </section>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Timestamp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Complaint</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User Activity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($logs as $log)
                                @php
                                    $eventValue = strtolower($log->event_type);
                                    $eventClass = match (true) {
                                        str_contains($eventValue, 'delete'), str_contains($eventValue, 'remove'), str_contains($eventValue, 'spam'), str_contains($eventValue, 'abusive') => 'bg-rose-100 text-rose-700',
                                        str_contains($eventValue, 'resolve'), str_contains($eventValue, 'close') => 'bg-emerald-100 text-emerald-700',
                                        str_contains($eventValue, 'assign'), str_contains($eventValue, 'status'), str_contains($eventValue, 'update') => 'bg-blue-100 text-blue-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $eventClass }}">
                                            {{ $log->eventTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $log->complaint?->reference_code ?? 'N/A' }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->complaint?->title ?? 'No linked complaint title' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $log->actor?->name ?? 'System' }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->actor?->role ?? 'Automated' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        <div class="max-h-40 space-y-1 overflow-auto rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            @if (count($log->userActivityLines()) > 0)
                                                @foreach ($log->userActivityLines() as $line)
                                                    <p>{{ $line }}</p>
                                                @endforeach
                                            @else
                                                <p>No additional user activity details.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-600">No audit logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
