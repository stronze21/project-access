<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PollController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim($request->string('q')->toString());
        $state = $request->string('state')->toString();

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
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('question', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($state === 'open') {
            $query
                ->where('is_active', true)
                ->where(function ($builder) {
                    $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                });
        }

        if ($state === 'closed') {
            $query->where(function ($builder) {
                $builder
                    ->where('is_active', false)
                    ->orWhere(function ($closedBuilder) {
                        $closedBuilder
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<', now());
                    });
            });
        }

        return view('polls.index', [
            'polls' => $query->paginate(8)->withQueryString(),
            'canManagePolls' => $this->canManagePolls($user),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManagePolls($request->user()), 403);

        return view('polls.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManagePolls($request->user()), 403);

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'options_text' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);

        $options = $this->parseOptions($validated['options_text']);
        if (count($options) < 2) {
            return back()
                ->withErrors(['options_text' => 'At least 2 poll options are required.'])
                ->withInput();
        }

        if (count($options) > 10) {
            return back()
                ->withErrors(['options_text' => 'Maximum 10 poll options allowed.'])
                ->withInput();
        }

        $lowercaseOptions = array_map('strtolower', $options);
        if (count(array_unique($lowercaseOptions)) !== count($lowercaseOptions)) {
            return back()
                ->withErrors(['options_text' => 'Duplicate poll options are not allowed.'])
                ->withInput();
        }

        $poll = DB::transaction(function () use ($request, $validated, $options) {
            $poll = Poll::create([
                'question' => $validated['question'],
                'description' => $validated['description'] ?? null,
                'created_by_user_id' => $request->user()->id,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'starts_at' => now(),
                'ends_at' => $validated['ends_at'] ?? null,
            ]);

            foreach ($options as $index => $optionText) {
                $poll->options()->create([
                    'option_text' => $optionText,
                    'sort_order' => $index + 1,
                ]);
            }

            return $poll;
        });

        return redirect()
            ->route('polls.show', $poll)
            ->with('status', 'Poll created successfully.');
    }

    public function show(Request $request, Poll $poll): View
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

        return view('polls.show', [
            'poll' => $poll,
            'canManagePolls' => $this->canManagePolls($request->user()),
        ]);
    }

    public function vote(Request $request, Poll $poll): RedirectResponse
    {
        $validated = $request->validate([
            'option_id' => [
                'required',
                Rule::exists('poll_options', 'id'),
            ],
        ]);

        if (!$poll->isVoteOpen()) {
            return back()->withErrors([
                'vote' => 'Voting is closed for this poll.',
            ]);
        }

        $selectedOption = $poll->options()->where('id', $validated['option_id'])->first();
        if ($selectedOption === null) {
            return back()->withErrors([
                'vote' => 'Selected option does not belong to this poll.',
            ]);
        }

        $existingVote = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingVote && (int) $existingVote->poll_option_id === (int) $selectedOption->id) {
            return back()->with('status', 'Your vote is already recorded.');
        }

        if ($existingVote) {
            $existingVote->update([
                'poll_option_id' => $selectedOption->id,
            ]);

            return back()->with('status', 'Your vote has been updated.');
        }

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $selectedOption->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Your vote has been submitted.');
    }

    /**
     * @return array<int, string>
     */
    private function parseOptions(string $optionsText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $optionsText) ?: [];

        return array_values(array_filter(array_map(
            fn ($line) => trim($line),
            $lines
        )));
    }

    private function canManagePolls(User $user): bool
    {
        return $user->isAdmin() || $user->isMayor();
    }
}
