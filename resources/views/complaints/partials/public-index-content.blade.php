@php
    $selectedStatus = request('status');
    $selectedCategoryId = (string) request('category_id');
    $selectedCategory = $categories->firstWhere('id', (int) $selectedCategoryId);
    $hasActiveFilters = filled($selectedStatus) || filled($selectedCategoryId);
    $canSubmitAnonymous = !auth()->check() || !auth()->user()->isInternalUser();
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-700 p-5 shadow-lg sm:p-7">
        <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-base-100/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-8 left-10 h-28 w-28 rounded-full bg-cyan-300/20 blur-2xl"></div>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100/90">Community Watch</p>
                <h1 class="mt-2 text-2xl font-bold text-white sm:text-3xl">Public Complaint Board</h1>
                <p class="mt-2 max-w-2xl text-sm text-blue-100">
                    Browse active and resolved community issues. Public view only includes summary-level information.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                @if ($canSubmitAnonymous)
                    <a href="{{ route('complaints.anonymous.create') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-white/30 bg-base-100/10 px-3 py-2 text-sm font-semibold text-white backdrop-blur hover:bg-base-100/20 btn btn-outline btn-sm">
                        Submit Anonymous
                    </a>
                @endif
                @auth
                    @if (auth()->user()->isCitizen())
                        <a href="{{ route('complaints.create') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-base-100 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50 btn btn-primary btn-sm">
                            Submit Verified
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        <div class="relative mt-5 grid grid-cols-2 gap-2 sm:max-w-md">
            <div class="rounded-lg border border-white/20 bg-base-100/10 px-3 py-2 backdrop-blur">
                <p class="text-[11px] uppercase tracking-wide text-blue-100">Visible Cases</p>
                <p class="text-lg font-bold text-white">{{ number_format($complaints->total()) }}</p>
            </div>
            <div class="rounded-lg border border-white/20 bg-base-100/10 px-3 py-2 backdrop-blur">
                <p class="text-[11px] uppercase tracking-wide text-blue-100">Current Page</p>
                <p class="text-lg font-bold text-white">{{ number_format($complaints->count()) }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-base-content">Filters</h2>
                <p class="text-xs text-base-content/60">Refine by status and category.</p>
            </div>
            @if ($hasActiveFilters)
                <a href="{{ route('complaints.public.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-base-300 px-3 py-2 text-xs font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-xs">
                    Clear All
                </a>
            @endif
        </div>

        <form method="GET" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label for="status" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Status</label>
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 select select-bordered">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="category_id" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Category</label>
                <select id="category_id" name="category_id" class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 select select-bordered">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                    Apply
                </button>
            </div>
        </form>

        @if ($hasActiveFilters)
            <div class="mt-3 flex flex-wrap gap-2">
                @if (filled($selectedStatus))
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 badge badge-sm">
                        Status: {{ str_replace('_', ' ', ucfirst($selectedStatus)) }}
                    </span>
                @endif
                @if (filled($selectedCategoryId) && $selectedCategory)
                    <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700 badge badge-sm">
                        Category: {{ $selectedCategory->name }}
                    </span>
                @endif
            </div>
        @endif
    </section>

    <section class="space-y-4">
        @forelse ($complaints as $complaint)
            @php
                $statusClass = match ($complaint->status) {
                    \App\Models\Complaint::STATUS_RECEIVED => 'bg-base-200 text-base-content/80',
                    \App\Models\Complaint::STATUS_ASSIGNED => 'bg-indigo-100 text-indigo-700',
                    \App\Models\Complaint::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
                    \App\Models\Complaint::STATUS_RESOLVED => 'bg-emerald-100 text-emerald-800',
                    \App\Models\Complaint::STATUS_CLOSED => 'bg-base-300 text-base-content/80',
                    default => 'bg-base-200 text-base-content/80',
                };

                $priorityClass = match ($complaint->priority) {
                    \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-100 text-rose-700',
                    \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-100 text-orange-700',
                    \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-700',
                    \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-100 text-emerald-700',
                    default => 'bg-base-200 text-base-content/80',
                };

                $leftAccentClass = match ($complaint->priority) {
                    \App\Models\Complaint::PRIORITY_URGENT => 'bg-rose-500',
                    \App\Models\Complaint::PRIORITY_HIGH => 'bg-orange-500',
                    \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-yellow-500',
                    \App\Models\Complaint::PRIORITY_LOW => 'bg-emerald-500',
                    default => 'bg-neutral',
                };
            @endphp

            <article class="group relative overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-sm transition hover:shadow-md card">
                <div class="absolute inset-y-0 left-0 w-1.5 {{ $leftAccentClass }}"></div>
                <div class="p-4 sm:p-5">
                    @if ($complaint->previewImageAttachment)
                        <a href="{{ route('complaints.preview-image', $complaint) }}"
                           data-complaint-lightbox-src="{{ route('complaints.preview-image', $complaint) }}"
                           data-complaint-lightbox-alt="{{ $complaint->title }}"
                           class="block">
                            <img src="{{ route('complaints.preview-image', $complaint) }}"
                                 alt="Complaint photo"
                                 class="mb-3 h-44 w-full cursor-zoom-in rounded-xl border border-base-300 object-cover sm:h-52">
                        </a>
                        <p class="-mt-1 mb-2 text-[11px] text-base-content/60">Tap image to enlarge</p>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-base-content sm:text-lg">{{ $complaint->title }}</h2>
                            <p class="mt-1 text-xs text-base-content/60">Reference: {{ $complaint->reference_code }}</p>
                            @if ($complaint->visibility === \App\Models\Complaint::VISIBILITY_PUBLIC_NAMED)
                                <div class="mt-2 flex items-center gap-2">
                                    <x-user-avatar :user="$complaint->submitter" size="h-7 w-7" textSize="text-[10px]" />
                                    <p class="text-xs text-base-content/70">
                                        Reported by {{ $complaint->submitter?->name ?? 'Citizen' }}
                                    </p>
                                </div>
                            @elseif ($complaint->visibility === \App\Models\Complaint::VISIBILITY_PUBLIC_ANONYMOUS)
                                <p class="mt-2 text-xs text-base-content/60">Reported anonymously</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClass }} badge badge-sm">
                                {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                            </span>
                            <span class="rounded-full px-2.5 py-1 font-semibold {{ $priorityClass }} badge badge-sm">
                                {{ ucfirst($complaint->priority ?? 'unprioritized') }}
                            </span>
                        </div>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed text-base-content/80">{{ $complaint->short_summary }}</p>

                    <dl class="mt-4 grid grid-cols-1 gap-2 text-sm text-base-content/70 sm:grid-cols-3">
                        <div class="rounded-lg bg-base-200 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Category</dt>
                            <dd class="mt-0.5 font-medium text-base-content/80">{{ $complaint->category?->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="rounded-lg bg-base-200 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Barangay</dt>
                            <dd class="mt-0.5 font-medium text-base-content/80">{{ $complaint->barangay?->name ?? 'Not specified' }}</dd>
                        </div>
                        <div class="rounded-lg bg-base-200 px-3 py-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Supports</dt>
                            <dd class="mt-0.5 font-medium text-base-content/80">{{ number_format((int) $complaint->support_count) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 rounded-xl border border-base-300 bg-base-100 p-3">
                        <x-complaint-progress :status="$complaint->status" />
                    </div>

                    @if ($complaint->resolution_summary)
                        <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Resolution Summary</p>
                            <p class="mt-1 text-sm text-emerald-900">
                                {{ \Illuminate\Support\Str::limit($complaint->resolution_summary, 180) }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-4 space-y-1 text-xs text-base-content/60">
                        <p>Submitted: {{ $complaint->submittedAtManila()?->format('M d, Y h:i A') ?? 'N/A' }}</p>
                        <p>Accomplished: {{ $complaint->accomplishedAtManila()?->format('M d, Y') ?? 'Not yet' }}</p>
                        <p>{{ $complaint->timeMetricTitle() }}: <span class="font-semibold text-base-content/80">{{ $complaint->runningTimeLabel() }}</span></p>
                    </div>

                    <div class="mt-4 rounded-xl border border-base-300 bg-base-200 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/70">
                                Public Comments ({{ number_format((int) $complaint->visible_comments_count) }})
                            </p>
                            <a href="{{ route('complaints.public.show', $complaint) }}"
                               class="text-xs font-semibold text-blue-700 hover:text-blue-800">
                                Open Thread
                            </a>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($complaint->visibleComments as $comment)
                                @php
                                    $currentReaction = auth()->check() ? $comment->reactions->first()?->reaction : null;
                                @endphp
                                <article id="comment-{{ $comment->id }}" class="rounded-lg border border-base-300 bg-base-100 p-2.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <x-user-avatar :user="$comment->user" size="h-7 w-7" textSize="text-[10px]" />
                                            <p class="truncate text-xs font-semibold text-base-content/80">
                                                {{ $comment->user?->name ?? 'Deleted user' }}
                                                @if ($comment->is_staff_response)
                                                    <span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700">
                                                        Official Response
                                                    </span>
                                                @endif
                                            </p>
                                        </div>
                                        <p class="text-[11px] text-base-content/60">{{ $comment->createdAtManila()?->format('M d, Y h:i A') }}</p>
                                    </div>
                                    <p class="mt-1.5 text-sm text-base-content/80">{{ $comment->body }}</p>

                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        @auth
                                            <form method="POST" action="{{ route('complaints.comments.react', [$complaint, $comment]) }}">
                                                @csrf
                                                <input type="hidden" name="reaction" value="like">
                                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}#comment-{{ $comment->id }}">
                                                <button type="submit"
                                                        aria-label="Like comment"
                                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold {{ $currentReaction === 'like' ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-300' : 'bg-base-200 text-base-content/80 hover:bg-base-300' }} btn btn-ghost btn-xs">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                                                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold {{ $currentReaction === 'dislike' ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-300' : 'bg-base-200 text-base-content/80 hover:bg-base-300' }} btn btn-ghost btn-xs">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M17 14V2"></path>
                                                        <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"></path>
                                                    </svg>
                                                    {{ number_format((int) $comment->dislikes_count) }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-md bg-base-200 px-2 py-1 text-[11px] font-semibold text-base-content/80">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M7 10v12"></path>
                                                    <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                                                </svg>
                                                {{ number_format((int) $comment->likes_count) }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-md bg-base-200 px-2 py-1 text-[11px] font-semibold text-base-content/80">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M17 14V2"></path>
                                                    <path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22a3.13 3.13 0 0 1-3-3.88Z"></path>
                                                </svg>
                                                {{ number_format((int) $comment->dislikes_count) }}
                                            </span>
                                            <a href="{{ route('login') }}" class="text-[11px] font-semibold text-blue-700 hover:text-blue-800">
                                                Login to react
                                            </a>
                                        @endauth
                                    </div>
                                </article>
                            @empty
                                <p class="text-xs text-base-content/60">No comments yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-end">
                        <a href="{{ route('complaints.public.show', $complaint) }}"
                           class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 btn btn-primary btn-sm">
                            View Details
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 p-8 text-center shadow-sm card">
                <p class="text-base font-semibold text-base-content">No public complaints found</p>
                <p class="mt-1 text-sm text-base-content/60">Try changing filters or submit a new complaint.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <a href="{{ route('complaints.public.index') }}"
                       class="inline-flex rounded-lg border border-base-300 px-3 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                        Reset Filters
                    </a>
                    @if ($canSubmitAnonymous)
                        <a href="{{ route('complaints.anonymous.create') }}"
                           class="inline-flex rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                            Submit Anonymous
                        </a>
                    @endif
                </div>
            </div>
        @endforelse
    </section>

    <div class="rounded-xl bg-base-100 px-4 py-3 shadow-sm">
        {{ $complaints->links() }}
    </div>
</div>

@include('complaints.partials.image-lightbox')
