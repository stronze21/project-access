<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Complaint Management Queue
            </h2>
            <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                {{ $scopeLabel }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Operations</p>
                    <h3 class="mt-2 text-xl font-bold sm:text-2xl">Actionable Queue Overview</h3>
                    <p class="mt-2 max-w-2xl text-sm text-slate-200">
                        Monitor active cases, prioritize urgent issues, and move complaints through resolution.
                    </p>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) $queueStats['total']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Open</p>
                    <p class="mt-1 text-xl font-bold text-blue-700">{{ number_format((int) $queueStats['open']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Overdue</p>
                    <p class="mt-1 text-xl font-bold text-rose-700">{{ number_format((int) $queueStats['overdue']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Urgent</p>
                    <p class="mt-1 text-xl font-bold text-orange-700">{{ number_format((int) $queueStats['urgent']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Resolved (Month)</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format((int) $queueStats['resolved_this_month']) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Filtered Result</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) $queueStats['filtered']) }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Queue Filters</h3>
                        <p class="text-xs text-slate-500">Find cases by status, urgency, department, and moderation state.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('complaints.manage.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Clear Filters
                        </a>
                    @endif
                </div>

                <form method="GET" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    @if (request()->filled('focus'))
                        <input type="hidden" name="focus" value="{{ request('focus') }}">
                    @endif

                    <div>
                        <label for="status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</label>
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All priorities</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="department_id" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Department</label>
                        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" @disabled($isRestrictedScope)>
                            <option value="">All departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="moderation_status" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Moderation</label>
                        <select id="moderation_status" name="moderation_status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All moderation states</option>
                            @foreach ($moderationStatuses as $moderationStatus)
                                <option value="{{ $moderationStatus }}" @selected(request('moderation_status') === $moderationStatus)>{{ ucfirst($moderationStatus) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($activeFilterLabels as $label)
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-4 md:hidden">
                @forelse ($complaints as $complaint)
                    @php
                        $statusClass = match ($complaint->status) {
                            \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
                            \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                            \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                            \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800',
                            \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-gray-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $priorityClass = match ($complaint->priority) {
                            \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
                            \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
                            \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-700',
                            \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-100 text-emerald-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $complaint->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $complaint->reference_code }}</p>
                            </div>
                            <div class="flex flex-col gap-1 text-[11px]">
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2 py-0.5 font-semibold {{ $statusClass }}">
                                    {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                                </span>
                                <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2 py-0.5 font-semibold {{ $priorityClass }}">
                                    {{ ucfirst($complaint->priority ?? 'N/A') }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Department</p>
                                <p class="mt-0.5 text-slate-700">{{ $complaint->assignedDepartment?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Officer</p>
                                <p class="mt-0.5 text-slate-700">{{ $complaint->assignedOfficer?->name ?? 'Unassigned' }}</p>
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Submitted</p>
                                <p class="mt-0.5 text-slate-700">{{ $complaint->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Accomplished Date</p>
                                <p class="mt-0.5 text-slate-700">{{ $complaint->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</p>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-slate-500">{{ $complaint->timeMetricTitle() }}: <span class="font-semibold text-slate-700">{{ $complaint->runningTimeLabel() }}</span></p>

                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-slate-500">{{ $complaint->category?->name }}</span>
                            <a href="{{ route('complaints.manage.show', $complaint) }}"
                               class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                Open Case
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">
                        No complaints found.
                    </div>
                @endforelse
            </section>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950/35 md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/80">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Officer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Accomplished Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-950/20">
                            @forelse ($complaints as $complaint)
                                @php
                                    $statusClass = match ($complaint->status) {
                                        \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
                                        \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                                        \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                                        \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800',
                                        \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-gray-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };

                                    $priorityClass = match ($complaint->priority) {
                                        \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
                                        \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
                                        \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-700',
                                        \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <tr class="transition-colors hover:bg-slate-50/70 dark:hover:bg-cyan-950/35">
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $complaint->reference_code }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $complaint->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $complaint->category?->name }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center justify-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClass }}">
                                            {{ ucfirst($complaint->priority ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $complaint->assignedDepartment?->name ?? 'Unassigned' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $complaint->assignedOfficer?->name ?? 'Unassigned' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $complaint->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $complaint->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <span class="font-semibold">{{ $complaint->timeMetricTitle() }}:</span>
                                        {{ $complaint->runningTimeLabel() }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('complaints.manage.show', $complaint) }}"
                                           class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-cyan-950/40">
                                            Open Case
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-600">No complaints found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $complaints->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
