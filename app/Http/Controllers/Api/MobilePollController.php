<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobilePollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim($request->string('q')->toString());
        $state = $request->string('state')->toString();
        $perPage = max(1, min(50, $request->integer('per_page', 10)));

        $query = Poll::query()
            ->with([
                'creator:id,name',
                'options' => fn ($optionQuery) => $optionQuery
                    ->select(['id', 'poll_id', 'option_text', 'sort_order'])
                    ->withCount('votes')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'votes' => fn ($voteQuery) => $voteQuery
                    ->select(['id', 'poll_id', 'poll_option_id', 'user_id'])
                    ->where('user_id', $user->id),
            ])
            ->withCount('votes')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('question', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($state === 'open') {
            $query
                ->where('is_active', true)
                ->where(function ($builder): void {
                    $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                });
        }

        if ($state === 'closed') {
            $query->where(function ($builder): void {
                $builder
                    ->where('is_active', false)
                    ->orWhere(function ($closedBuilder): void {
                        $closedBuilder
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<', now());
                    });
            });
        }

        $polls = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $polls
                ->getCollection()
                ->map(fn (Poll $poll): array => $this->serializePoll($poll))
                ->values(),
            'meta' => [
                'current_page' => $polls->currentPage(),
                'last_page' => $polls->lastPage(),
                'per_page' => $polls->perPage(),
                'total' => $polls->total(),
            ],
        ]);
    }

    public function show(Request $request, Poll $poll): JsonResponse
    {
        $poll->load([
            'creator:id,name',
            'options' => fn ($optionQuery) => $optionQuery
                ->select(['id', 'poll_id', 'option_text', 'sort_order'])
                ->withCount('votes')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'votes' => fn ($voteQuery) => $voteQuery
                ->select(['id', 'poll_id', 'poll_option_id', 'user_id'])
                ->where('user_id', $request->user()->id),
        ])->loadCount('votes');

        return response()->json([
            'data' => $this->serializePoll($poll),
        ]);
    }

    public function vote(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'option_id' => [
                'required',
                Rule::exists('poll_options', 'id'),
            ],
        ]);

        if (!$poll->isVoteOpen()) {
            return response()->json([
                'message' => 'Voting is closed for this poll.',
            ], 422);
        }

        $selectedOption = $poll->options()->where('id', $validated['option_id'])->first();
        if ($selectedOption === null) {
            return response()->json([
                'message' => 'Selected option does not belong to this poll.',
            ], 422);
        }

        $statusMessage = DB::transaction(function () use ($request, $poll, $selectedOption): string {
            $existingVote = PollVote::query()
                ->where('poll_id', $poll->id)
                ->where('user_id', $request->user()->id)
                ->first();

            if ($existingVote && (int) $existingVote->poll_option_id === (int) $selectedOption->id) {
                return 'Your vote is already recorded.';
            }

            if ($existingVote) {
                $existingVote->update([
                    'poll_option_id' => $selectedOption->id,
                ]);

                return 'Your vote has been updated.';
            }

            PollVote::create([
                'poll_id' => $poll->id,
                'poll_option_id' => $selectedOption->id,
                'user_id' => $request->user()->id,
            ]);

            return 'Your vote has been submitted.';
        });

        $poll->load([
            'creator:id,name',
            'options' => fn ($optionQuery) => $optionQuery
                ->select(['id', 'poll_id', 'option_text', 'sort_order'])
                ->withCount('votes')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'votes' => fn ($voteQuery) => $voteQuery
                ->select(['id', 'poll_id', 'poll_option_id', 'user_id'])
                ->where('user_id', $request->user()->id),
        ])->loadCount('votes');

        return response()->json([
            'message' => $statusMessage,
            'data' => $this->serializePoll($poll),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePoll(Poll $poll): array
    {
        $totalVotes = (int) ($poll->votes_count ?? 0);
        $selectedOptionId = $poll->votes->first()?->poll_option_id;

        return [
            'id' => $poll->id,
            'question' => $poll->question,
            'description' => $poll->description,
            'is_active' => (bool) $poll->is_active,
            'starts_at' => $poll->starts_at?->toISOString(),
            'ends_at' => $poll->ends_at?->toISOString(),
            'is_vote_open' => $poll->isVoteOpen(),
            'votes_count' => $totalVotes,
            'selected_option_id' => $selectedOptionId ? (int) $selectedOptionId : null,
            'creator' => $poll->creator
                ? [
                    'id' => $poll->creator->id,
                    'name' => $poll->creator->name,
                ]
                : null,
            'options' => $poll->options->map(
                fn (PollOption $option): array => [
                    'id' => $option->id,
                    'text' => $option->option_text,
                    'votes_count' => (int) ($option->votes_count ?? 0),
                    'percentage' => $totalVotes > 0
                        ? round(((int) ($option->votes_count ?? 0) / $totalVotes) * 100, 2)
                        : 0,
                ]
            )->values(),
        ];
    }
}

