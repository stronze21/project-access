@foreach ($comments as $comment)
    @php
        $isOwnComment = (int) $comment->user_id === (int) auth()->id();
        $isSoftDeletedComment = $comment->trashed();
        $isPermanentDeletedComment = (bool) $comment->is_permanently_deleted;
        $isCommentLocked = (bool) $post->is_comments_locked;
        $currentCommentReaction = $commentMyReactions[(int) $comment->id] ?? null;
        $currentCommentReactionMeta = $currentCommentReaction ? ($reactionTypes[$currentCommentReaction] ?? null) : null;
        $indent = min((int) $depth, 8) * 16;
    @endphp

    <article id="comment-{{ $comment->id }}"
             class="rounded-xl border border-base-300 bg-base-100 p-3 shadow-sm"
             style="margin-left: {{ $indent }}px;">
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <x-user-avatar :user="$comment->author" size="h-7 w-7" textSize="text-[10px]" />
                <p class="truncate text-xs font-semibold text-base-content/80">
                    {{ $comment->author?->name ?? 'Deleted user' }}
                </p>
            </div>
            <p class="shrink-0 text-[11px] text-base-content/60">
                {{ $comment->createdAtManila()?->format('M d, Y h:i A') }}
            </p>
        </div>

        @if ($isSoftDeletedComment)
            <p class="mt-2 text-sm italic text-base-content/60">This comment was deleted.</p>
        @elseif ($isPermanentDeletedComment)
            <p class="mt-2 text-sm italic text-base-content/60">This comment was permanently removed by moderation.</p>
        @else
            @if ($comment->hidden_at && ($isOwnComment || $canModerate))
                <p class="mt-2 text-[11px] font-semibold text-amber-700">Hidden due to reports/moderation.</p>
            @endif

            <p class="mt-2 text-sm text-base-content/80 whitespace-pre-line">{{ $comment->body }}</p>

            @if ($comment->edited_at)
                <p class="mt-1 text-[11px] text-base-content/60">
                    Edited at {{ $comment->editedAtManila()?->format('h:i A') }}
                </p>
            @endif
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-1.5">
            @if (!$isSoftDeletedComment && !$isPermanentDeletedComment)
                <div x-data="{ reactionOpen: false }"
                     @keydown.escape.window="reactionOpen = false"
                     class="relative"
                     @mouseenter="reactionOpen = true"
                     @mouseleave="reactionOpen = false">
                    <button type="button"
                            @click="reactionOpen = !reactionOpen"
                            title="{{ $currentCommentReactionMeta['label'] ?? 'React' }}"
                            aria-label="{{ $currentCommentReactionMeta['label'] ?? 'React' }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-base-200 text-base text-base-content/80 transition hover:bg-base-300 btn btn-ghost btn-sm btn-circle">
                        <span>{{ $currentCommentReactionMeta['emoji'] ?? ':)' }}</span>
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
                            <form method="POST" action="{{ route('sentiments.comments.react', $comment) }}">
                                @csrf
                                <input type="hidden" name="reaction" value="{{ $reactionKey }}">
                                <button type="submit"
                                        title="{{ $reactionMeta['label'] }}"
                                        aria-label="{{ $reactionMeta['label'] }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-xl transition hover:-translate-y-1 hover:scale-125 focus:outline-none focus:ring-2 focus:ring-blue-400 {{ $currentCommentReaction === $reactionKey ? 'bg-blue-50' : '' }} btn btn-ghost btn-sm btn-circle">
                                    <span>{{ $reactionMeta['emoji'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!$isSoftDeletedComment && !$isPermanentDeletedComment)
                <button type="button"
                        data-reactors-url="{{ route('sentiments.comments.reactors', $comment) }}"
                        title="View reactions"
                        class="inline-flex h-8 items-center gap-1 rounded-full bg-base-200 px-3 text-[11px] font-semibold text-base-content/80 hover:bg-base-300 btn btn-ghost btn-xs">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10v12M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                    </svg>
                    {{ number_format((int) $comment->reactions_count) }}
                </button>
            @else
                <span class="inline-flex h-8 items-center gap-1 rounded-full bg-base-200 px-3 text-[11px] font-semibold text-base-content/80 badge badge-sm">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10v12M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                    </svg>
                    {{ number_format((int) $comment->reactions_count) }}
                </span>
            @endif

            @if (!$isSoftDeletedComment && !$isPermanentDeletedComment && !$isCommentLocked && !$isPostingBanned)
                <details>
                    <summary class="cursor-pointer rounded-md bg-base-200 px-2 py-1 text-[11px] font-semibold text-base-content/80 hover:bg-base-300">
                        Reply
                    </summary>
                    <form method="POST" action="{{ route('sentiments.comments.store', $post) }}" class="mt-2 space-y-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <textarea name="body"
                                  rows="2"
                                  required
                                  maxlength="5000"
                                  class="w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 textarea textarea-bordered"
                                  placeholder="Write a reply..."></textarea>
                        <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 btn btn-primary btn-xs">
                            Reply
                        </button>
                    </form>
                </details>
            @endif

            @if ($isOwnComment && !$isSoftDeletedComment && !$isPermanentDeletedComment)
                <details>
                    <summary class="cursor-pointer rounded-md bg-base-200 px-2 py-1 text-[11px] font-semibold text-base-content/80 hover:bg-base-300">
                        Edit
                    </summary>
                    <form method="POST" action="{{ route('sentiments.comments.update', $comment) }}" class="mt-2 space-y-2">
                        @csrf
                        @method('PUT')
                        <textarea name="body"
                                  rows="2"
                                  required
                                  maxlength="5000"
                                  class="w-full rounded-lg border-base-300 text-sm focus:border-blue-500 focus:ring-blue-500 textarea textarea-bordered">{{ $comment->body }}</textarea>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 btn btn-primary btn-xs">
                            Save
                        </button>
                    </form>
                </details>

                <form method="POST" action="{{ route('sentiments.comments.destroy', $comment) }}"
                      onsubmit="return confirm('Delete this comment?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-200 btn btn-error btn-xs">
                        Delete
                    </button>
                </form>
            @endif

            @if (!$isOwnComment && !$isSoftDeletedComment && !$isPermanentDeletedComment)
                <form method="POST" action="{{ route('sentiments.comments.report', $comment) }}">
                    @csrf
                    <input type="hidden" name="reason" value="Reported from feed">
                    <button type="submit" class="rounded-md bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-200 btn btn-warning btn-xs">
                        Report
                    </button>
                </form>
            @endif

            @if ($canModerate)
                <form method="POST" action="{{ route('sentiments.comments.moderate', $comment) }}">
                    @csrf
                    <input type="hidden" name="action" value="restore">
                    <button type="submit" class="rounded-md bg-emerald-100 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-200 btn btn-success btn-xs">
                        Restore
                    </button>
                </form>
                <form method="POST" action="{{ route('sentiments.comments.moderate', $comment) }}">
                    @csrf
                    <input type="hidden" name="action" value="permanent_delete">
                    <button type="submit" class="rounded-md bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-200 btn btn-error btn-xs">
                        Remove
                    </button>
                </form>
                <form method="POST" action="{{ route('sentiments.comments.moderate', $comment) }}">
                    @csrf
                    <input type="hidden" name="action" value="ban_user">
                    <input type="hidden" name="note" value="Banned via comment moderation.">
                    <button type="submit" class="rounded-md bg-orange-100 px-2 py-1 text-[11px] font-semibold text-orange-700 hover:bg-orange-200 btn btn-warning btn-xs">
                        Ban Author
                    </button>
                </form>
            @endif
        </div>
    </article>

    @php
        $childComments = $childrenMap->get($comment->id, collect());
    @endphp
    @if ($childComments->isNotEmpty())
        @include('sentiments.partials.comment-tree', [
            'comments' => $childComments,
            'childrenMap' => $childrenMap,
            'post' => $post,
            'depth' => $depth + 1,
            'reactionTypes' => $reactionTypes,
            'commentMyReactions' => $commentMyReactions,
            'canModerate' => $canModerate,
            'isPostingBanned' => $isPostingBanned,
        ])
    @endif
@endforeach
