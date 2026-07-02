<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Case {{ $complaint->reference_code }}
                </h2>
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                    Management
                </span>
            </div>
            <a href="{{ route('complaints.manage.index') }}"
               class="inline-flex w-fit rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Queue
            </a>
        </div>
    </x-slot>

    @php
        $statusClass = match ($complaint->status) {
            \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
            \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
            \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
            \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-700',
            \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-slate-700',
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

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Case Overview</p>
                    <h3 class="text-xl font-bold sm:text-2xl">{{ $complaint->title }}</h3>
                    <p class="max-w-3xl text-sm text-slate-100/90">{{ $complaint->short_summary }}</p>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClass }}">
                            {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                        </span>
                        <span class="rounded-full px-2.5 py-1 font-semibold {{ $priorityClass }}">
                            Priority: {{ ucfirst($complaint->priority ?? 'N/A') }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                            Moderation: {{ ucfirst($complaint->moderation_status) }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Submitted Date</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->submittedAtManila()?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Submitted Time</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->submittedAtManila()?->format('h:i A') ?? 'N/A' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Accomplished Date</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $complaint->timeMetricTitle() }}</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->runningTimeLabel() }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Department</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $complaint->assignedDepartment?->name ?? 'Unassigned' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Officer</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $complaint->assignedOfficer?->name ?? 'Unassigned' }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Category</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $complaint->category?->name ?? 'N/A' }}</p>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <section class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900">Case Details</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Submitted {{ $complaint->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}
                    </p>
                    <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-800">
                        <span class="font-semibold">Full Description:</span> {{ $complaint->description }}
                    </p>

                    @if ($complaint->resolution_summary)
                        <p class="mt-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                            <span class="font-semibold">Resolution Summary:</span> {{ $complaint->resolution_summary }}
                        </p>
                    @endif

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 sm:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Status</dt>
                            <dd>{{ str_replace('_', ' ', ucfirst($complaint->status)) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Priority</dt>
                            <dd>{{ ucfirst($complaint->priority ?? 'N/A') }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Category</dt>
                            <dd>{{ $complaint->category?->name }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Barangay</dt>
                            <dd>{{ $complaint->barangay?->name ?? 'Not specified' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Department</dt>
                            <dd>{{ $complaint->assignedDepartment?->name ?? 'Unassigned' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="font-semibold text-slate-800">Action Officer</dt>
                            <dd>{{ $complaint->assignedOfficer?->name ?? 'Unassigned' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="space-y-4">
                    @if (auth()->user()->isAdmin() || auth()->user()->isMayor())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Assign Department</h4>
                            <form method="POST" action="{{ route('complaints.manage.assign-department', $complaint) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="department_id" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    <option value="">Select department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((int) $complaint->assigned_department_id === (int) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="reason" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Reason (optional)">
                                <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    Save Department
                                </button>
                            </form>
                        </div>
                    @endif

                    @if (auth()->user()->isAdmin() || auth()->user()->isMayor() || auth()->user()->isDepartmentHead())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Assign Action Officer</h4>
                            <form method="POST" action="{{ route('complaints.manage.assign-officer', $complaint) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="officer_id" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    <option value="">Select officer</option>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}" @selected((int) $complaint->assigned_officer_id === (int) $officer->id)>{{ $officer->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="reason" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Reason (optional)">
                                <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    Save Officer
                                </button>
                            </form>
                        </div>
                    @endif

                    @if (auth()->user()->isAdmin())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Set Priority (Admin only)</h4>
                            <form method="POST" action="{{ route('complaints.manage.set-priority', $complaint) }}" class="mt-3 flex gap-2">
                                @csrf
                                <select name="priority" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority }}" @selected($complaint->priority === $priority)>{{ ucfirst($priority) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    Set
                                </button>
                            </form>
                        </div>
                    @endif

                    @can('updateStatus', $complaint)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Update Status</h4>
                            <form method="POST" action="{{ route('complaints.manage.status', $complaint) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="status" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($statuses as $status)
                                        @if ($status !== \App\Models\Complaint::STATUS_RECEIVED)
                                            <option value="{{ $status }}" @selected($complaint->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <textarea name="resolution_summary" rows="3" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Required when resolving">{{ old('resolution_summary', $complaint->resolution_summary) }}</textarea>
                                <input type="text" name="note" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Note (optional)">
                                <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                    Update Status
                                </button>
                            </form>
                        </div>
                    @endcan

                    @if (auth()->user()->isAdmin())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Moderation (Admin only)</h4>
                            <form method="POST" action="{{ route('complaints.manage.moderate', $complaint) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="moderation_status" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($moderationStatuses as $moderationStatus)
                                        <option value="{{ $moderationStatus }}" @selected($complaint->moderation_status === $moderationStatus)>{{ ucfirst($moderationStatus) }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="moderation_reason" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Reason if non-normal">
                                <button type="submit" class="inline-flex rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                                    Save Moderation
                                </button>
                            </form>
                        </div>
                    @endif

                    @if (auth()->user()->isMayor())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-900">Mayor Override</h4>
                            <form method="POST" action="{{ route('complaints.manage.override', $complaint) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="action" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                    <option value="reopen">Reopen (Resolved -> In Progress)</option>
                                    <option value="escalate">Escalate</option>
                                </select>
                                <input type="text" name="note" class="block w-full rounded-lg border-slate-300 text-sm" placeholder="Override note (optional)">
                                <button type="submit" class="inline-flex rounded-lg bg-purple-600 px-3 py-2 text-xs font-semibold text-white hover:bg-purple-700">
                                    Apply Override
                                </button>
                            </form>
                        </div>
                    @endif
                </section>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Internal Notes</h3>
                    @can('addInternalNote', $complaint)
                        <form method="POST" action="{{ route('complaints.manage.internal-note', $complaint) }}" class="mt-3 space-y-2">
                            @csrf
                            <textarea name="note" rows="3" class="block w-full rounded-lg border-slate-300 text-sm" required></textarea>
                            <button type="submit" class="inline-flex rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">
                                Add Internal Note
                            </button>
                        </form>
                    @endcan

                    <div class="mt-4 space-y-2">
                        @forelse ($complaint->internalNotes as $note)
                            <article class="rounded-xl border border-slate-200 p-3 text-sm">
                                <p class="font-semibold text-slate-800">{{ $note->user?->name ?? 'Unknown' }}</p>
                                <p class="mt-1 text-slate-700">{{ $note->note }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $note->created_at?->format('M d, Y h:i A') }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-slate-600">No internal notes yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Public Comments</h3>
                    <div class="mt-4 space-y-2">
                        @forelse ($complaint->comments as $comment)
                            <article class="rounded-xl border border-slate-200 p-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-slate-800">{{ $comment->user?->name ?? 'Deleted user' }}</p>
                                    <p class="text-xs text-slate-500">{{ $comment->createdAtManila()?->format('M d, Y h:i A') }}</p>
                                </div>
                                <p class="mt-1 {{ $comment->is_hidden ? 'text-slate-400 line-through' : 'text-slate-700' }}">
                                    {{ $comment->body }}
                                </p>
                                @if ($comment->is_hidden)
                                    <p class="mt-1 text-xs text-rose-600">Hidden: {{ $comment->hidden_reason }}</p>
                                @endif

                                @if (auth()->user()->isAdmin() && !$comment->is_hidden)
                                    <form method="POST" action="{{ route('complaints.manage.comments.hide', [$complaint, $comment]) }}" class="mt-2 flex gap-2">
                                        @csrf
                                        <input type="text" name="reason" class="block w-full rounded-lg border-slate-300 text-xs" placeholder="Reason to hide comment" required>
                                        <button type="submit" class="inline-flex rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                                            Hide
                                        </button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-slate-600">No comments yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Attachment Access (Internal)</h3>
                    @can('uploadAttachment', $complaint)
                        <form method="POST" action="{{ route('complaints.manage.attachments.store', $complaint) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                            @csrf
                            <select name="type" class="block w-full rounded-lg border-slate-300 text-sm" required>
                                <option value="evidence">Evidence</option>
                                <option value="resolution">Resolution</option>
                            </select>
                            <input type="file" name="files[]" multiple class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            <button type="submit" class="inline-flex rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">
                                Upload Attachments
                            </button>
                        </form>
                    @endcan

                    @can('downloadAttachment', $complaint)
                        <div class="mt-4 space-y-2">
                            @forelse ($complaint->attachments as $attachment)
                                <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-2 text-sm">
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ strtoupper($attachment->type) }} | {{ $attachment->virus_scan_status }} | {{ number_format($attachment->size_bytes / 1024, 1) }} KB
                                        </p>
                                    </div>
                                    <a href="{{ route('complaints.manage.attachments.download', [$complaint, $attachment]) }}"
                                       class="inline-flex rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Download
                                    </a>
                                </div>
                            @empty
                                <p class="text-sm text-slate-600">No attachments uploaded.</p>
                            @endforelse
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-600">Your role does not have attachment access.</p>
                    @endcan
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Official Tags & History</h3>

                    @if (auth()->user()->isAdmin() || auth()->user()->isMayor())
                        <form method="POST" action="{{ route('complaints.manage.official-tags', $complaint) }}" class="mt-3 space-y-2">
                            @csrf
                            <select name="official_ids[]" multiple class="block w-full rounded-lg border-slate-300 text-sm">
                                @php
                                    $selectedOfficials = $complaint->officials->pluck('id')->map(fn ($id) => (int) $id)->all();
                                @endphp
                                @foreach ($officials as $official)
                                    <option value="{{ $official->id }}" @selected(in_array((int) $official->id, $selectedOfficials, true))>
                                        {{ $official->position }} - {{ $official->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                Save Tags
                            </button>
                        </form>
                    @endif

                    <div class="mt-4 space-y-2">
                        <h4 class="text-sm font-semibold text-slate-800">Status History</h4>
                        @forelse ($complaint->statusHistories as $history)
                            <article class="rounded-xl border border-slate-200 p-2 text-sm">
                                <p class="font-semibold text-slate-800">
                                    {{ str_replace('_', ' ', ucfirst($history->from_status ?? 'none')) }} -> {{ str_replace('_', ' ', ucfirst($history->to_status)) }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $history->changedBy?->name ?? 'System' }} | {{ $history->created_at?->format('M d, Y h:i A') }}
                                    @if ($history->is_override)
                                        | Override
                                    @endif
                                </p>
                                @if ($history->note)
                                    <p class="mt-1 text-slate-700">{{ $history->note }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-slate-600">No status history.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>


