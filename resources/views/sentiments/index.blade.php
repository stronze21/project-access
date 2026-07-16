<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-base-content/90 leading-tight">People Sentiments</h2>
                <p class="text-xs text-base-content/60">Global Facebook-like feed for all signed-in users.</p>
            </div>
            <span class="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700 badge badge-sm">
                Live Feed
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl-removed space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-gradient-to-br from-slate-900 via-cyan-900 to-blue-900 p-5 text-white shadow-lg sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100">Community Pulse</p>
                <h3 class="mt-2 text-2xl font-bold">One Global Feed</h3>
                <p class="mt-2 max-w-3xl text-sm text-cyan-100/90">
                    Share updates, react, comment, mention users, and report content. Feed order is hybrid by default, with a separate trending tab.
                </p>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="xl:col-span-2 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('sentiments.index', request()->except(['page'])) }}"
                           class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold {{ $activeMode === 'feed' ? 'bg-blue-600 text-white' : 'bg-base-100 text-base-content/80 ring-1 ring-base-300 hover:bg-base-200' }}">
                            Global Feed
                        </a>
                        <a href="{{ route('sentiments.trending', request()->except(['page'])) }}"
                           class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold {{ $activeMode === 'trending' ? 'bg-blue-600 text-white' : 'bg-base-100 text-base-content/80 ring-1 ring-base-300 hover:bg-base-200' }}">
                            Trending
                        </a>
                    </div>

                    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-base-content">Create Post</h3>
                            <span class="text-[11px] text-base-content/60">Max 5,000 characters · Real identity only</span>
                        </div>

                        @if ($isPostingBanned)
                            <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                Posting is disabled for your account by moderation.
                            </div>
                        @else
                            <form method="POST" action="{{ route('sentiments.posts.store') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                @csrf
                                <textarea name="body"
                                          rows="4"
                                          maxlength="5000"
                                          class="w-full rounded-xl border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 textarea textarea-bordered"
                                          placeholder="What's on your mind? Use @name to mention someone.">{{ old('body') }}</textarea>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div>
                                        <label for="media" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Upload Photo/Video (Optional)</label>
                                        <input id="media"
                                               type="file"
                                               name="media"
                                               accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                                               class="mt-1 block w-full rounded-lg border border-base-300 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-base-200 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-base-content/80 hover:file:bg-base-300 file-input file-input-bordered">
                                    </div>
                                    <div>
                                        <label for="external_url" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">External Media URL (Optional)</label>
                                        <input id="external_url"
                                               type="url"
                                               name="external_url"
                                               value="{{ old('external_url') }}"
                                               class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 input input-bordered"
                                               placeholder="https://...">
                                    </div>
                                </div>

                                @error('body')
                                    <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('media')
                                    <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('external_url')
                                    <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                                    Publish Post
                                </button>
                            </form>
                        @endif
                    </section>

                    @php
                        $hasAdvancedFilters = request()->filled('q')
                            || request()->filled('author_id')
                            || request()->filled('reaction_type')
                            || request()->filled('date_from')
                            || request()->filled('date_to')
                            || request()->filled('comment_sort');
                    @endphp
                    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg [&::-webkit-details-marker]:hidden">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-base-content">Advanced Search</h3>
                                    @if ($hasAdvancedFilters)
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700 badge badge-sm">Filters Active</span>
                                    @endif
                                </div>
                                <span class="inline-flex rounded-md bg-base-200 px-2 py-1 text-[11px] font-semibold text-base-content/70">Expand / Collapse</span>
                            </summary>

                            <div class="mt-3 flex justify-end">
                                <a href="{{ $activeMode === 'trending' ? route('sentiments.trending') : route('sentiments.index') }}"
                                   class="text-xs font-semibold text-base-content/70 hover:text-base-content">
                                    Clear Filters
                                </a>
                            </div>

                            <form method="GET" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                                <div class="xl:col-span-2">
                                    <label for="q" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Keyword</label>
                                    <input id="q"
                                           type="text"
                                           name="q"
                                           value="{{ request('q') }}"
                                           class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 input input-bordered"
                                           placeholder="Search posts/comments">
                                </div>
                                <div>
                                    <label for="author_id" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Author</label>
                                    <select id="author_id" name="author_id" class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 select select-bordered">
                                        <option value="">All</option>
                                        @foreach ($authors as $author)
                                            <option value="{{ $author->id }}" @selected((string) request('author_id') === (string) $author->id)>{{ $author->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="reaction_type" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Reaction</label>
                                    <select id="reaction_type" name="reaction_type" class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 select select-bordered">
                                        <option value="">Any</option>
                                        @foreach ($reactionTypes as $reactionKey => $reactionMeta)
                                            <option value="{{ $reactionKey }}" @selected(request('reaction_type') === $reactionKey)>{{ $reactionMeta['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="date_from" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Date From</label>
                                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 input input-bordered">
                                </div>
                                <div>
                                    <label for="date_to" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Date To</label>
                                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 input input-bordered">
                                </div>
                                <div>
                                    <label for="comment_sort" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Comments Sort</label>
                                    <select id="comment_sort" name="comment_sort" class="mt-1 w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 select select-bordered">
                                        <option value="newest" @selected($commentSort === 'newest')>Newest</option>
                                        <option value="oldest" @selected($commentSort === 'oldest')>Oldest</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2 xl:col-span-6">
                                    <button type="submit" class="btn btn-neutral btn-sm">
                                        Apply Filters
                                    </button>
                                </div>
                            </form>
                        </details>
                    </section>

                    <div id="sentiment-feed-container">
                        @include('sentiments.partials.feed-list')
                    </div>
                </div>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-base-content">In-App Notifications</h3>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 badge badge-sm">
                                {{ number_format((int) $unreadNotificationCount) }} unread
                            </span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse ($notifications as $notification)
                                @php($data = is_array($notification->data) ? $notification->data : [])
                                <article class="rounded-lg border border-base-300 bg-base-200 px-3 py-2">
                                    <p class="text-xs font-semibold text-base-content">{{ $data['title'] ?? 'Notification' }}</p>
                                    <p class="mt-0.5 text-xs text-base-content/70">{{ $data['message'] ?? 'You have an update.' }}</p>
                                    <p class="mt-1 text-[11px] text-base-content/60">
                                        {{ $notification->created_at?->timezone(config('sentiments.timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}
                                    </p>
                                </article>
                            @empty
                                <p class="text-xs text-base-content/60">No notifications yet.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-base-content">Rules Snapshot</h3>
                        <ul class="mt-3 space-y-2 text-xs text-base-content/70">
                            <li>One global feed, all signed-in users.</li>
                            <li>No anonymous posting in this module.</li>
                            <li>One reaction per user per post/comment.</li>
                            <li>Reports auto-hide content at 10 reports.</li>
                            <li>Moderators: Admin and Mayor.</li>
                        </ul>
                    </section>
                </aside>
            </section>
        </div>
    </div>

    <div id="sentiment-reactor-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" data-reactor-close></div>
        <div class="absolute left-1/2 top-1/2 w-[92vw] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-base-100 p-4 shadow-xl sm:p-5 card">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-base-content">Reactions</h3>
                <button type="button" class="rounded-md bg-base-200 px-2 py-1 text-xs font-semibold text-base-content/80 hover:bg-base-300 btn btn-ghost btn-xs" data-reactor-close>
                    Close
                </button>
            </div>
            <div id="sentiment-reactor-content" class="mt-3 max-h-[60vh] space-y-2 overflow-y-auto text-sm text-base-content/80"></div>
        </div>
    </div>

    <script>
        (() => {
            const feedContainer = document.getElementById('sentiment-feed-container');
            if (!feedContainer) {
                return;
            }

            const pollIntervalMs = {{ (int) $pollIntervalMs }};
            const fragmentUrl = new URL(@json(route('sentiments.fragment')));
            const activeMode = @json($activeMode);

            const syncFeed = async () => {
                if (document.hidden) {
                    return;
                }

                const activeElement = document.activeElement;
                if (activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) {
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.delete('partial');
                url.searchParams.delete('mode');
                url.searchParams.set('mode', activeMode);

                const requestUrl = new URL(fragmentUrl.toString());
                requestUrl.search = url.searchParams.toString();

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const html = await response.text();
                    feedContainer.innerHTML = html;
                } catch (error) {
                    // Keep UI quiet during polling failures.
                }
            };

            setInterval(syncFeed, pollIntervalMs);

            const modal = document.getElementById('sentiment-reactor-modal');
            const modalContent = document.getElementById('sentiment-reactor-content');
            if (!modal || !modalContent) {
                return;
            }

            const closeModal = () => {
                modal.classList.add('hidden');
                modalContent.innerHTML = '';
            };

            document.addEventListener('click', async (event) => {
                const closeTrigger = event.target.closest('[data-reactor-close]');
                if (closeTrigger) {
                    closeModal();
                    return;
                }

                const trigger = event.target.closest('[data-reactors-url]');
                if (!trigger) {
                    return;
                }

                const url = trigger.getAttribute('data-reactors-url');
                if (!url) {
                    return;
                }

                try {
                    modal.classList.remove('hidden');
                    modalContent.innerHTML = '<p class="text-xs text-base-content/60">Loading reactions...</p>';

                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        modalContent.innerHTML = '<p class="text-xs text-rose-600">Failed to load reactions.</p>';
                        return;
                    }

                    const payload = await response.json();
                    const groups = payload.groups || {};
                    const labels = payload.labels || {};
                    const rows = [];

                    Object.keys(labels).forEach((key) => {
                        const names = Array.isArray(groups[key]) ? groups[key] : [];
                        if (!names.length) {
                            return;
                        }

                        const label = labels[key]?.label || key;
                        const emoji = labels[key]?.emoji || '';
                        rows.push(
                            `<div class="rounded-lg border border-base-300 bg-base-200 px-3 py-2">
                                <p class="text-xs font-semibold text-base-content">${emoji} ${label} (${names.length})</p>
                                <p class="mt-1 text-xs text-base-content/70">${names.join(', ')}</p>
                            </div>`
                        );
                    });

                    modalContent.innerHTML = rows.length
                        ? rows.join('')
                        : '<p class="text-xs text-base-content/60">No reactions yet.</p>';
                } catch (error) {
                    modalContent.innerHTML = '<p class="text-xs text-rose-600">Failed to load reactions.</p>';
                }
            });
        })();
    </script>
</x-app-layout>
