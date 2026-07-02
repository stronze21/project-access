@php
    $statusClass = match ($complaint->status) {
        \App\Models\Complaint::STATUS_RECEIVED => 'bg-slate-100 text-slate-700',
        \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
        \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
        \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-700',
        \App\Models\Complaint::STATUS_CLOSED => 'bg-gray-200 text-gray-700',
        default => 'bg-slate-100 text-slate-700',
    };

    $priorityClass = match ($complaint->priority) {
        \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
        \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
        \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-700',
        \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-100 text-emerald-700',
        default => 'bg-blue-50 text-blue-700',
    };
@endphp

<div class="space-y-5">
    <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900 p-5 text-white shadow-lg sm:p-6">
        <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

        <div class="relative">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Public Case Detail</p>
                    <h1 class="mt-2 text-xl font-bold sm:text-2xl">{{ $complaint->title }}</h1>
                    <p class="mt-1 text-xs text-slate-200">Reference: {{ $complaint->reference_code }}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClass }}">
                        {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                    </span>
                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $priorityClass }}">
                        {{ ucfirst($complaint->priority ?? 'Unprioritized') }}
                    </span>
                </div>
            </div>

            <p class="mt-4 max-w-3xl text-sm text-slate-100/90">{{ $complaint->short_summary }}</p>
        </div>
    </article>

    @if ($complaint->previewImageAttachment)
        <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <a href="{{ route('complaints.preview-image', $complaint) }}"
               data-complaint-lightbox-src="{{ route('complaints.preview-image', $complaint) }}"
               data-complaint-lightbox-alt="{{ $complaint->title }}"
               class="block">
                <img src="{{ route('complaints.preview-image', $complaint) }}"
                     alt="Complaint photo"
                     class="h-64 w-full cursor-zoom-in rounded-xl object-cover sm:h-80">
            </a>
            <p class="mt-2 text-xs text-slate-500">Tap image to enlarge</p>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <x-complaint-progress :status="$complaint->status" />
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Category</p>
            <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->category?->name ?? 'N/A' }}</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Barangay</p>
            <p class="mt-1 text-sm font-bold text-slate-900">{{ $complaint->barangay?->name ?? 'Not specified' }}</p>
        </div>
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Support Count</p>
            <p class="mt-1 text-sm font-bold text-slate-900">{{ number_format((int) $complaint->support_count) }}</p>
        </div>
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
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        @if ($complaint->visibility === \App\Models\Complaint::VISIBILITY_PUBLIC_NAMED)
            <div class="flex items-center gap-3">
                <x-user-avatar :user="$complaint->submitter" size="h-10 w-10" textSize="text-sm" />
                <p class="text-sm text-slate-600">
                    Reported by:
                    <span class="font-semibold text-slate-800">
                        {{ $complaint->submitter?->name ?? $complaint->reporter_name ?? 'Citizen' }}
                    </span>
                </p>
            </div>
        @endif

        @if ($complaint->officials->isNotEmpty())
            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tagged Public Officials</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($complaint->officials as $official)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
                            {{ $official->position }}: {{ $official->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($complaint->resolution_summary)
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Resolution Summary</p>
                <p class="mt-1 text-sm text-emerald-900">{{ $complaint->resolution_summary }}</p>
            </div>
        @endif

        <div class="mt-5 flex flex-wrap gap-2">
            @auth
                @can('support', $complaint)
                    <form method="POST" action="{{ route('complaints.support', $complaint) }}">
                        @csrf
                        <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            I Have the Same Issue
                        </button>
                    </form>
                @endcan
            @endauth
            <a href="{{ route('complaints.public.index') }}" class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to List
            </a>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-slate-900">Public Comments</h2>

        <div class="mt-4 space-y-3">
            @forelse ($complaint->visibleComments as $comment)
                @php
                    $currentReaction = auth()->check() ? $comment->reactions->first()?->reaction : null;
                @endphp
                <article id="comment-{{ $comment->id }}" class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <x-user-avatar :user="$comment->user" size="h-8 w-8" textSize="text-xs" />
                            <p class="truncate text-sm font-semibold text-slate-800">
                                {{ $comment->user?->name ?? 'Deleted user' }}
                                @if ($comment->is_staff_response)
                                    <span class="ml-2 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Official Response</span>
                                @endif
                            </p>
                        </div>
                        <p class="text-xs text-slate-500">{{ $comment->createdAtManila()?->format('M d, Y h:i A') }}</p>
                    </div>
                    <p class="mt-2 text-sm text-slate-700">{{ $comment->body }}</p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @auth
                            <form method="POST" action="{{ route('complaints.comments.react', [$complaint, $comment]) }}">
                                @csrf
                                <input type="hidden" name="reaction" value="like">
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}#comment-{{ $comment->id }}">
                                <button type="submit"
                                        aria-label="Like comment"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold {{ $currentReaction === 'like' ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M7 10v12"></path>
                                        <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                                    </svg>
                                    {{ number_format((int) $comment->likes_count) }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('complaints.comments.react', [$complaint, $comment]) }}">
                                @csrf
                                <input type="hidden" name="reaction" value="dislike">
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}#comment-{{ $comment->id }}">
                                <button type="submit"
                                        aria-label="Dislike comment"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold {{ $currentReaction === 'dislike' ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17 14V2"></path>
                                        <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"></path>
                                    </svg>
                                    {{ number_format((int) $comment->dislikes_count) }}
                                </button>
                            </form>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M7 10v12"></path>
                                    <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                                </svg>
                                {{ number_format((int) $comment->likes_count) }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 14V2"></path>
                                    <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"></path>
                                </svg>
                                {{ number_format((int) $comment->dislikes_count) }}
                            </span>
                            <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-700 hover:text-blue-800">
                                Login to react
                            </a>
                        @endauth
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-600">No public comments yet.</p>
            @endforelse
        </div>

        @auth
            <form method="POST" action="{{ route('complaints.comments.store', $complaint) }}" class="mt-4 space-y-2">
                @csrf
                <label for="body" class="text-sm font-semibold text-slate-700">Add Comment</label>
                <textarea id="body" name="body" rows="3" class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="inline-flex rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                    Post Comment
                </button>
            </form>
        @else
            <p class="mt-4 text-sm text-slate-600">
                <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('login') }}">Login</a>
                to comment or support this issue.
            </p>
        @endauth
    </section>
</div>

@include('complaints.partials.image-lightbox')
