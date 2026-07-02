<?php

namespace App\Services;

use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Models\SentimentReport;
use App\Models\User;
use App\Notifications\SentimentInAppNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SentimentService
{
    /**
     * @return array<int, string>
     */
    public function reactionKeys(): array
    {
        return array_keys(config('sentiments.reaction_types', []));
    }

    /**
     * @return Collection<int, User>
     */
    public function extractMentionedUsers(string $text, ?int $excludeUserId = null): Collection
    {
        preg_match_all('/@([A-Za-z0-9._-]{2,50})/', $text, $matches);
        $tokens = collect($matches[1] ?? [])
            ->map(fn ($token) => strtolower(trim((string) $token)))
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $query = User::query()->where(function ($builder) use ($tokens): void {
            foreach ($tokens as $token) {
                $builder->orWhereRaw('LOWER(REPLACE(name, " ", "")) = ?', [$token]);
                $builder->orWhereRaw('LOWER(SUBSTRING_INDEX(name, " ", 1)) = ?', [$token]);
            }
        });

        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->get(['id', 'name']);
    }

    public function notifyMentions(
        string $text,
        User $actor,
        string $contextType,
        int $contextId,
        string $url
    ): void {
        $mentionedUsers = $this->extractMentionedUsers($text, $actor->id);
        if ($mentionedUsers->isEmpty()) {
            return;
        }

        foreach ($mentionedUsers as $mentionedUser) {
            $mentionedUser->notify(new SentimentInAppNotification([
                'type' => 'sentiment_mention',
                'title' => 'You were mentioned',
                'message' => $actor->name.' mentioned you in a '.$contextType.'.',
                'url' => $url,
                'meta' => [
                    'actor_user_id' => $actor->id,
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                ],
            ]));
        }
    }

    public function notifyCommentOnPost(User $postOwner, User $actor, SentimentPost $post): void
    {
        if ((int) $postOwner->id === (int) $actor->id) {
            return;
        }

        $postOwner->notify(new SentimentInAppNotification([
            'type' => 'sentiment_comment',
            'title' => 'New comment on your post',
            'message' => $actor->name.' commented on your post.',
            'url' => route('sentiments.index', ['post' => $post->id]).'#post-'.$post->id,
            'meta' => [
                'actor_user_id' => $actor->id,
                'post_id' => $post->id,
            ],
        ]));
    }

    public function notifyReaction(User $targetOwner, User $actor, string $reaction, string $targetType, int $targetId): void
    {
        if ((int) $targetOwner->id === (int) $actor->id) {
            return;
        }

        $reactionLabel = Str::title(str_replace('_', ' ', $reaction));
        $targetOwner->notify(new SentimentInAppNotification([
            'type' => 'sentiment_reaction',
            'title' => 'New reaction',
            'message' => $actor->name.' reacted '.$reactionLabel.' on your '.$targetType.'.',
            'url' => route('sentiments.index'),
            'meta' => [
                'actor_user_id' => $actor->id,
                'reaction' => $reaction,
                'target_type' => $targetType,
                'target_id' => $targetId,
            ],
        ]));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function groupedReactorNames(Model $reactionable): array
    {
        $reactionable->loadMissing('reactions.user:id,name');
        $groups = [];
        foreach ($this->reactionKeys() as $reactionType) {
            $groups[$reactionType] = [];
        }

        /** @var SentimentReaction $reaction */
        foreach ($reactionable->reactions as $reaction) {
            $type = strtolower((string) $reaction->reaction);
            if (!array_key_exists($type, $groups)) {
                $groups[$type] = [];
            }

            $name = trim((string) ($reaction->user?->name ?? 'Unknown'));
            if ($name !== '') {
                $groups[$type][] = $name;
            }
        }

        return $groups;
    }

    public function resolveReportsAndNotify(Model $reportable, string $decisionStatus, User $moderator): void
    {
        $openReports = SentimentReport::query()
            ->where('reportable_type', $reportable::class)
            ->where('reportable_id', $reportable->getKey())
            ->where('status', SentimentReport::STATUS_OPEN)
            ->with('reporter:id,name')
            ->get();

        if ($openReports->isEmpty()) {
            return;
        }

        SentimentReport::query()
            ->whereIn('id', $openReports->pluck('id'))
            ->update([
                'status' => $decisionStatus,
                'reviewed_by_user_id' => $moderator->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $title = $decisionStatus === SentimentReport::STATUS_RESOLVED_DELETE
            ? 'Reported content was removed'
            : 'Reported content was restored';
        $message = $decisionStatus === SentimentReport::STATUS_RESOLVED_DELETE
            ? 'A moderator removed the content you reported.'
            : 'A moderator restored the content you reported.';

        $reporters = $openReports
            ->pluck('reporter')
            ->filter()
            ->unique('id');

        foreach ($reporters as $reporter) {
            $reporter->notify(new SentimentInAppNotification([
                'type' => 'sentiment_report_decision',
                'title' => $title,
                'message' => $message.' Reviewed by '.$moderator->name.'.',
                'url' => route('sentiments.index'),
                'meta' => [
                    'reviewed_by_user_id' => $moderator->id,
                    'reportable_type' => $reportable::class,
                    'reportable_id' => $reportable->getKey(),
                    'decision' => $decisionStatus,
                ],
            ]));
        }
    }

    public function isModerator(User $user): bool
    {
        return $user->isAdmin() || $user->isMayor();
    }

    public function isPostingBanned(User $user): bool
    {
        return $user->isSentimentPostingBanned();
    }

    public function canViewPost(User $viewer, SentimentPost $post): bool
    {
        return $post->isVisibleTo($viewer);
    }

    public function canViewComment(User $viewer, SentimentComment $comment): bool
    {
        return $comment->isVisibleTo($viewer);
    }
}
