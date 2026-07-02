<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Complaints</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('complaints.quick.create') }}"
                   class="inline-flex items-center rounded-lg border border-cyan-300 bg-cyan-50 px-3 py-2 text-sm font-semibold text-cyan-800 hover:bg-cyan-100">
                    Quick Ticket
                </a>
                <a href="{{ route('complaints.create') }}"
                   class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    New Complaint
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $pageItems = $complaints->getCollection();
        $resolvedStatuses = [\App\Models\Complaint::STATUS_RESOLVED, \App\Models\Complaint::STATUS_CLOSED];
        $resolvedOnPage = $pageItems->whereIn('status', $resolvedStatuses)->count();
        $activeOnPage = $pageItems->count() - $resolvedOnPage;
        $workflow = [
            \App\Models\Complaint::STATUS_RECEIVED,
            \App\Models\Complaint::STATUS_ASSIGNED,
            \App\Models\Complaint::STATUS_IN_PROGRESS,
            \App\Models\Complaint::STATUS_RESOLVED,
            \App\Models\Complaint::STATUS_CLOSED,
        ];
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-sky-900 to-cyan-700 p-5 shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-10 h-28 w-28 rounded-full bg-cyan-200/20 blur-2xl"></div>

                <div class="relative grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Total Tickets</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ number_format($complaints->total()) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Active (This Page)</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ number_format($activeOnPage) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Resolved/Closed (This Page)</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ number_format($resolvedOnPage) }}</p>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                @forelse ($complaints as $complaint)
                    @php
                        $statusClass = match ($complaint->status) {
                            \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
                            \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                            \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                            \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-700',
                            \App\Models\Complaint::STATUS_CLOSED => 'bg-zinc-200 text-zinc-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $priorityClass = match ($complaint->priority) {
                            \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
                            \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
                            \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-amber-100 text-amber-700',
                            \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-100 text-emerald-700',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $statusIndex = array_search($complaint->status, $workflow, true);
                        $statusIndex = $statusIndex === false ? 0 : $statusIndex;
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $complaint->reference_code }}</p>
                                    <h3 class="mt-1 truncate text-base font-semibold text-slate-900 sm:text-lg">{{ $complaint->title }}</h3>
                                    <p class="mt-1 text-xs text-slate-600">{{ $complaint->category?->name ?? 'Uncategorized' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClass }}">
                                        {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $priorityClass }}">
                                        {{ ucfirst($complaint->priority ?? 'N/A') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-4 sm:p-5">
                            @if ($complaint->previewImageAttachment)
                                <div>
                                    <a href="{{ route('complaints.preview-image', $complaint) }}"
                                       data-complaint-lightbox-src="{{ route('complaints.preview-image', $complaint) }}"
                                       data-complaint-lightbox-alt="{{ $complaint->title }}"
                                       class="block">
                                        <img src="{{ route('complaints.preview-image', $complaint) }}"
                                             alt="Complaint photo"
                                             class="h-44 w-full cursor-zoom-in rounded-xl border border-slate-200 object-cover sm:h-56">
                                    </a>
                                    <p class="mt-1 text-[11px] text-slate-500">Tap image to enlarge</p>
                                </div>
                            @endif

                            <p class="text-sm leading-relaxed text-slate-700">
                                {{ \Illuminate\Support\Str::limit($complaint->short_summary ?? $complaint->description, 220) }}
                            </p>

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Submitted</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                        {{ $complaint->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Accomplished</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                        {{ $complaint->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ $complaint->timeMetricTitle() }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $complaint->runningTimeLabel() }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Department</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $complaint->assignedDepartment?->name ?? 'Not assigned' }}</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Progress</p>
                                <div class="overflow-x-auto pb-1">
                                    <div class="flex min-w-max items-center gap-2">
                                        @foreach ($workflow as $stepIndex => $step)
                                            @php
                                                $isDone = $stepIndex <= $statusIndex;
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex min-w-[110px] items-center justify-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $isDone ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ str_replace('_', ' ', ucfirst($step)) }}
                                                </span>
                                                @if (!$loop->last)
                                                    <span class="h-px w-6 {{ $isDone ? 'bg-blue-300' : 'bg-slate-300' }}"></span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-1">
                                <a href="{{ route('complaints.public.show', $complaint) }}"
                                   class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    View Details
                                </a>

                                @if ($complaint->status === \App\Models\Complaint::STATUS_RECEIVED && $complaint->assigned_department_id === null)
                                    <a href="{{ route('complaints.edit', $complaint) }}"
                                       class="inline-flex rounded-lg border border-blue-300 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                        Edit Ticket
                                    </a>
                                @endif

                                @if ($complaint->status === \App\Models\Complaint::STATUS_RESOLVED)
                                    <form method="POST" action="{{ route('complaints.confirm-resolution', $complaint) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Confirm Resolution
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                        <p class="text-base font-semibold text-slate-800">No complaints submitted yet</p>
                        <p class="mt-1 text-sm text-slate-500">Start with a quick ticket or submit a detailed complaint.</p>
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                            <a href="{{ route('complaints.quick.create') }}"
                               class="inline-flex rounded-lg border border-cyan-300 bg-cyan-50 px-3 py-2 text-sm font-semibold text-cyan-800 hover:bg-cyan-100">
                                Quick Ticket
                            </a>
                            <a href="{{ route('complaints.create') }}"
                               class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                New Complaint
                            </a>
                        </div>
                    </div>
                @endforelse
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $complaints->links() }}
            </div>
        </div>
    </div>

    @include('complaints.partials.image-lightbox')
</x-app-layout>
