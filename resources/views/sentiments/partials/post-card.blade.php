@php
    $isOwnPost = (int) $post->user_id === (int) auth()->id();
    $isSoftDeletedPost = $post->trashed();
    $isPermanentDeletedPost = (bool) $post->is_permanently_deleted;
    $isFollowedAuthor = $followingAuthorIds->contains((int) $post->user_id);
    $currentPostReaction = $postMyReactions[(int) $post->id] ?? null;
    $currentPostReactionMeta = $currentPostReaction ? ($reactionTypes[$currentPostReaction] ?? null) : null;
    $commentChildrenMap = $post->comments->groupBy(fn ($item) => $item->parent_id ?: 0);
    $rootComments = $commentChildrenMap->get(0, collect());
    $latestComment = $post->comments->sortByDesc('created_at')->first();
    $latestCommentPreview = null;
    if ($latestComment) {
        if ($latestComment->trashed()) {
            $latestCommentPreview = 'This comment was deleted.';
        } elseif ($latestComment->is_permanently_deleted) {
            $latestCommentPreview = 'This comment was permanently removed by moderation.';
        } else {
            $latestCommentPreview = \Illuminate\Support\Str::limit((string) $latestComment->body, 160);
        }
    }
@endphp

<article id="post-{{ $post->id }}"
         x-data="{ commentsOpen: false }"
         class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <x-user-avatar :user="$post->author" size="h-9 w-9" textSize="text-xs" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-base-content">{{ $post->author?->name ?? 'Deleted user' }}</p>
                    <p class="text-[11px] text-base-content/60">
                        {{ $post->createdAtManila()?->format('M d, Y h:i A') }}
                        @if ($post->edited_at)
                            - Edited at {{ $post->editedAtManila()?->format('h:i A') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($post->is_pinned)
                <span class="rounded-full bg-cyan-100 px-2 py-1 text-[11px] font-semibold text-cyan-700 badge badge-sm">Pinned</span>
            @endif
            @if ($post->is_comments_locked)
                <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 badge badge-sm">Comments Locked</span>
            @endif
            @if ($post->hidden_at && ($isOwnPost || $canModerate))
                <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 badge badge-sm">Hidden</span>
            @endif
        </div>
    </div>

    <div class="mt-3 space-y-3">
        @if ($isSoftDeletedPost)
            <p class="rounded-lg bg-base-200 px-3 py-3 text-sm italic text-base-content/60">This post was deleted.</p>
        @elseif ($isPermanentDeletedPost)
            <p class="rounded-lg bg-base-200 px-3 py-3 text-sm italic text-base-content/60">This post was permanently removed by moderation.</p>
        @else
            @if ($post->body)
                <p class="whitespace-pre-line text-sm leading-relaxed text-base-content/80">{{ $post->body }}</p>
            @endif

            @if ($post->media_kind === \App\Models\SentimentPost::MEDIA_IMAGE)
                <img src="{{ route('sentiments.posts.media', $post) }}"
                     alt="Post image"
                     class="max-h-[28rem] w-full rounded-xl border border-base-300 object-cover">
            @elseif ($post->media_kind === \App\Models\SentimentPost::MEDIA_VIDEO)
                <video controls class="max-h-[28rem] w-full rounded-xl border border-base-300 bg-black">
                    <source src="{{ route('sentiments.posts.media', $post) }}" type="{{ $post->media_mime_type }}">
                    Your browser does not support the video tag.
                </video>
            @elseif ($post->media_kind === \App\Models\SentimentPost::MEDIA_EXTERNAL && $post->external_url)
                <a href="{{ $post->external_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                    Open External Media Link
                </a>
            @endif
        @endif
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-base-300 pt-3">
        @if (!$isSoftDeletedPost && !$isPermanentDeletedPost)
            <div x-data="{ reactionOpen: false }"
                 @keydown.escape.window="reactionOpen = false"
                 class="relative"
                 @mouseenter="reactionOpen = true"
                 @mouseleave="reactionOpen = false">
                <button type="button"
                        @click="reactionOpen = !reactionOpen"
                        title="{{ $currentPostReactionMeta['label'] ?? 'React' }}"
                        aria-label="{{ $currentPostReactionMeta['label'] ?? 'React' }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-base-200 text-base text-base-content/80 transition hover:bg-base-300 btn btn-ghost btn-sm btn-circle">
                    <span>{{ $currentPostReactionMeta['emoji'] ?? ':)' }}</span>
                </button>

                <div x-show="reactionOpen"
                     @click.outside="reactionOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute bottom-full left-0 z-10 mb-2 flex items-center gap-1 rounded-full border border-base-300 bg-base-100 px-2 py-1 shadow-lg">
                    @foreach ($reactionTypes as $reactionKey => $reactionMeta)
                        <form method="POST" action="{{ route('sentiments.posts.react', $post) }}">
                            @csrf
                            <input type="hidden" name="reaction" value="{{ $reactionKey }}">
                            <button type="submit"
                                    title="{{ $reactionMeta['label'] }}"
                                    aria-label="{{ $reactionMeta['label'] }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-xl transition hover:-translate-y-1 hover:scale-125 focus:outline-none focus:ring-2 focus:ring-blue-400 {{ $currentPostReaction === $reactionKey ? 'bg-blue-50' : '' }} btn btn-ghost btn-sm btn-circle">
                                <span>{{ $reactionMeta['emoji'] }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!$isSoftDeletedPost && !$isPermanentDeletedPost)
            <button type="button"
                    data-reactors-url="{{ route('sentiments.posts.reactors', $post) }}"
                    title="View reactions"
                    class="inline-flex h-8 items-center gap-1 rounded-full bg-base-200 px-3 text-[11px] font-semibold text-base-content/80 hover:bg-base-300 btn btn-ghost btn-xs">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10v12M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                </svg>
                {{ number_format((int) $post->reactions_count) }}
            </button>
        @else
            <span class="inline-flex h-8 items-center gap-1 rounded-full bg-base-200 px-3 text-[11px] font-semibold text-base-content/80 badge badge-sm">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10v12M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                </svg>
                {{ number_format((int) $post->reactions_count) }}
            </span>
        @endif

        <button type="button"
                @click="commentsOpen = !commentsOpen"
                :aria-expanded="commentsOpen.toString()"
                title="Comments"
                class="inline-flex h-8 items-center gap-1 rounded-full bg-base-200 px-3 text-[11px] font-semibold text-base-content/80 transition hover:bg-base-300 btn btn-ghost btn-xs">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            {{ number_format((int) $post->comments_count) }}
        </button>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-1.5">
        @if (!$isOwnPost)
            @if ($isFollowedAuthor)
                <form method="POST" action="{{ route('sentiments.users.unfollow', $post->author) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            title="Unfollow"
                            aria-label="Unfollow"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-base-200 text-base-content/80 hover:bg-base-300 btn btn-ghost btn-sm btn-circle">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 19h6M8 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8M2 21a6 6 0 0 1 12 0"></path>
                        </svg>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('sentiments.users.follow', $post->author) }}">
                    @csrf
                    <button type="submit"
                            title="Follow"
                            aria-label="Follow"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyan-100 text-cyan-700 hover:bg-cyan-200 btn btn-primary btn-sm btn-circle">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6"></path>
                        </svg>
                    </button>
                </form>
            @endif
        @endif

        @if ($isOwnPost && !$isSoftDeletedPost && !$isPermanentDeletedPost)
            <details>
                <summary class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-base-200 text-base-content/80 hover:bg-base-300"
                         title="Edit post"
                         aria-label="Edit post">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                    </svg>
                </summary>
                <form method="POST" action="{{ route('sentiments.posts.update', $post) }}" class="mt-2 space-y-2">
                    @csrf
                    @method('PUT')
                    <textarea name="body"
                              rows="3"
                              required
                              maxlength="5000"
                              class="w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 textarea textarea-bordered">{{ $post->body }}</textarea>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 btn btn-primary btn-xs">
                        Save
                    </button>
                </form>
            </details>

            <form method="POST" action="{{ route('sentiments.posts.destroy', $post) }}"
                  onsubmit="return confirm('Delete this post?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        title="Delete post"
                        aria-label="Delete post"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700 hover:bg-rose-200 btn btn-error btn-sm btn-circle">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m-9 0 1 14a1 1 0 0 0 1 .93h6a1 1 0 0 0 1-.93L17 6"></path>
                    </svg>
                </button>
            </form>
        @endif

        @if (!$isOwnPost && !$isSoftDeletedPost && !$isPermanentDeletedPost)
            <form method="POST" action="{{ route('sentiments.posts.report', $post) }}">
                @csrf
                <input type="hidden" name="reason" value="Reported from feed">
                <button type="submit"
                        title="Report post"
                        aria-label="Report post"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 btn btn-warning btn-sm btn-circle">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16M4 5h11l-1 4 1 4H4"></path>
                    </svg>
                </button>
            </form>
        @endif

        @if ($canModerate && !$isSoftDeletedPost)
            <form method="POST" action="{{ route('sentiments.posts.moderate', $post) }}">
                @csrf
                <input type="hidden" name="action" value="restore">
                <button type="submit" class="rounded-md bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-200 btn btn-success btn-xs">
                    Restore
                </button>
            </form>
            <form method="POST" action="{{ route('sentiments.posts.moderate', $post) }}">
                @csrf
                <input type="hidden" name="action" value="permanent_delete">
                <button type="submit" class="rounded-md bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-200 btn btn-error btn-xs">
                    Remove
                </button>
            </form>
            <form method="POST" action="{{ route('sentiments.posts.moderate', $post) }}">
                @csrf
                <input type="hidden" name="action" value="{{ $post->is_comments_locked ? 'unlock_comments' : 'lock_comments' }}">
                <button type="submit" class="rounded-md bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-200 btn btn-warning btn-xs">
                    {{ $post->is_comments_locked ? 'Unlock Comments' : 'Lock Comments' }}
                </button>
            </form>
            <form method="POST" action="{{ route('sentiments.posts.moderate', $post) }}">
                @csrf
                <input type="hidden" name="action" value="{{ $post->is_pinned ? 'unpin' : 'pin' }}">
                <button type="submit" class="rounded-md bg-cyan-100 px-2 py-1 text-[11px] font-semibold text-cyan-700 hover:bg-cyan-200 btn btn-primary btn-xs">
                    {{ $post->is_pinned ? 'Unpin' : 'Pin' }}
                </button>
            </form>
            <form method="POST" action="{{ route('sentiments.posts.moderate', $post) }}">
                @csrf
                <input type="hidden" name="action" value="ban_user">
                <input type="hidden" name="note" value="Banned via post moderation.">
                <button type="submit" class="rounded-md bg-orange-100 px-2 py-1 text-[11px] font-semibold text-orange-700 hover:bg-orange-200 btn btn-warning btn-xs">
                    Ban Author
                </button>
            </form>
        @endif
    </div>

    @if (!$isSoftDeletedPost && !$isPermanentDeletedPost)
        <div x-show="!commentsOpen" class="mt-4 rounded-xl border border-base-300 bg-base-200 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">Most Recent Comment</p>
            @if ($latestComment)
                <div class="mt-2 flex items-start gap-2">
                    <x-user-avatar :user="$latestComment->author" size="h-7 w-7" textSize="text-[10px]" />
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-base-content/80">{{ $latestComment->author?->name ?? 'Deleted user' }}</p>
                        <p class="mt-1 text-sm text-base-content/70">{{ $latestCommentPreview }}</p>
                    </div>
                </div>
            @else
                <p class="mt-2 text-sm text-base-content/60">No comments yet. Tap the message icon to start the discussion.</p>
            @endif
        </div>
    @endif

    <div x-show="commentsOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="mt-4 rounded-xl border border-base-300 bg-base-200 p-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/70">Comments</p>

        @if ($post->is_comments_locked)
            <p class="mt-2 text-xs font-semibold text-amber-700">Comments are locked on this post.</p>
        @endif

        @if ($isPostingBanned)
            <p class="mt-2 text-xs font-semibold text-rose-700">Your posting access is disabled by moderation.</p>
        @endif

        @if (!$isSoftDeletedPost && !$isPermanentDeletedPost && !$post->is_comments_locked && !$isPostingBanned)
            <form method="POST" action="{{ route('sentiments.comments.store', $post) }}" class="mt-3 space-y-2">
                @csrf
                <textarea name="body"
                          rows="2"
                          required
                          maxlength="5000"
                          class="w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 textarea textarea-bordered"
                          placeholder="Write a comment..."></textarea>
                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 btn btn-primary btn-xs">
                    Comment
                </button>
            </form>
        @endif

        <div class="mt-3 space-y-2">
            @include('sentiments.partials.comment-tree', [
                'comments' => $rootComments,
                'childrenMap' => $commentChildrenMap,
                'post' => $post,
                'depth' => 0,
                'reactionTypes' => $reactionTypes,
                'commentMyReactions' => $commentMyReactions,
                'canModerate' => $canModerate,
                'isPostingBanned' => $isPostingBanned,
            ])
        </div>
    </div>
</article>
