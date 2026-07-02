<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Ticket Dashboard
            </h2>
            <a href="{{ route('complaints.create') }}"
               class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 sm:text-sm">
                Submit Ticket
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-12 h-28 w-28 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Citizen View</p>
                    <h3 class="mt-2 text-xl font-bold sm:text-2xl">Your Tickets and Status</h3>
                    <p class="mt-2 max-w-2xl text-sm text-cyan-100/90">
                        This dashboard only shows your own complaints, current status, and progress details.
                    </p>
                </div>
            </section>

            <section class="space-y-4 md:hidden">
                @forelse ($tickets as $ticket)
                    @php
                        $statusClass = match ($ticket->status) {
                            \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
                            \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                            \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                            \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800',
                            \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-gray-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $priorityClass = match ($ticket->priority) {
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
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $ticket->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $ticket->reference_code }}</p>
                            </div>
                            <div class="flex flex-col gap-1 text-[11px]">
                                <span class="rounded-full px-2 py-0.5 font-semibold {{ $statusClass }}">
                                    {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 font-semibold {{ $priorityClass }}">
                                    {{ ucfirst($ticket->priority ?? 'N/A') }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Category</p>
                                <p class="mt-0.5 text-slate-700">{{ $ticket->category?->name ?? 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Department</p>
                                <p class="mt-0.5 text-slate-700">{{ $ticket->assignedDepartment?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Submitted</p>
                                <p class="mt-0.5 text-slate-700">{{ $ticket->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Accomplished Date</p>
                                <p class="mt-0.5 text-slate-700">{{ $ticket->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</p>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            {{ $ticket->timeMetricTitle() }}:
                            <span class="font-semibold text-slate-700">{{ $ticket->runningTimeLabel() }}</span>
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($ticket->status === \App\Models\Complaint::STATUS_RECEIVED && $ticket->assigned_department_id === null)
                                <a href="{{ route('complaints.edit', $ticket) }}"
                                   class="inline-flex rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                    Edit
                                </a>
                            @endif
                            @if ($ticket->status === \App\Models\Complaint::STATUS_RESOLVED)
                                <form method="POST" action="{{ route('complaints.confirm-resolution', $ticket) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                        Confirm Resolution
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">
                        You have not submitted any tickets yet.
                    </div>
                @endforelse
            </section>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Accomplished</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($tickets as $ticket)
                                @php
                                    $statusClass = match ($ticket->status) {
                                        \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
                                        \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                                        \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                                        \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800',
                                        \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-gray-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->reference_code }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $ticket->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $ticket->category?->name ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->assignedDepartment?->name ?? 'Unassigned' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $ticket->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <span class="font-semibold">{{ $ticket->timeMetricTitle() }}:</span>
                                        {{ $ticket->runningTimeLabel() }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($ticket->status === \App\Models\Complaint::STATUS_RECEIVED && $ticket->assigned_department_id === null)
                                                <a href="{{ route('complaints.edit', $ticket) }}"
                                                   class="inline-flex rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                                    Edit
                                                </a>
                                            @endif
                                            @if ($ticket->status === \App\Models\Complaint::STATUS_RESOLVED)
                                                <form method="POST" action="{{ route('complaints.confirm-resolution', $ticket) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                                        Confirm
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-600">You have not submitted any tickets yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
