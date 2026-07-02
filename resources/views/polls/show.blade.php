<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Poll Details</h2>
            <div class="flex flex-wrap gap-2">
                @if ($canManagePolls)
                    <a href="{{ route('polls.create') }}"
                       class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Create Poll
                    </a>
                @endif
                <a href="{{ route('polls.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Polls
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $totalVotes = (int) $poll->votes_count;
        $selectedOptionId = (int) optional($poll->votes->first())->poll_option_id;
        $selectedOption = $poll->options->firstWhere('id', $selectedOptionId);
        $isOpen = $poll->isVoteOpen();
        $topOption = $poll->options->sortByDesc('votes_count')->first();
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-cyan-900 to-teal-700 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-16 h-24 w-24 rounded-full bg-cyan-200/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Community Poll</p>
                        <h1 class="mt-2 text-xl font-bold leading-tight sm:text-2xl">{{ $poll->question }}</h1>
                        <p class="mt-2 text-sm text-cyan-100/90">
                            By {{ $poll->creator?->name ?? 'System' }} &middot; {{ $poll->created_at?->format('M d, Y h:i A') }}
                        </p>
                        @if ($poll->description)
                            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-cyan-50/95">{{ $poll->description }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full border border-white/30 bg-white/10 px-2.5 py-1 font-semibold backdrop-blur">
                            {{ $isOpen ? 'Open for Voting' : 'Voting Closed' }}
                        </span>
                        <span class="rounded-full border border-white/30 bg-white/10 px-2.5 py-1 font-semibold backdrop-blur">
                            {{ number_format($totalVotes) }} Total Votes
                        </span>
                    </div>
                </div>

                <div class="relative mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Options</p>
                        <p class="mt-1 text-lg font-bold text-white">{{ number_format($poll->options->count()) }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Top Choice</p>
                        <p class="mt-1 truncate text-sm font-semibold text-white">{{ $topOption?->option_text ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Your Vote</p>
                        <p class="mt-1 truncate text-sm font-semibold text-white">{{ $selectedOption?->option_text ?? 'Not voted yet' }}</p>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 backdrop-blur">
                        <p class="text-[11px] uppercase tracking-wide text-cyan-100">Ends At</p>
                        <p class="mt-1 text-sm font-semibold text-white">{{ $poll->ends_at?->format('M d, Y h:i A') ?? 'No end date' }}</p>
                    </div>
                </div>
            </section>

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700">Options and Live Results</h3>
                            <p class="text-xs text-slate-500">Tap <span class="font-semibold">Vote</span> to submit or change your answer while voting is open.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full px-2.5 py-1 font-semibold {{ $isOpen ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $isOpen ? 'Open' : 'Closed' }}
                            </span>
                            @if ($selectedOption)
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-700">You voted</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-3 p-4 sm:p-6">
                    @foreach ($poll->options as $option)
                        @php
                            $optionVotes = (int) $option->votes_count;
                            $percentage = $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100, 1) : 0;
                            $isSelected = $selectedOptionId === (int) $option->id;
                            $barWidth = $totalVotes > 0 ? max(2, $percentage) : 0;
                        @endphp

                        <div class="rounded-xl border p-3 sm:p-4 {{ $isSelected ? 'border-blue-300 bg-blue-50/40 ring-1 ring-blue-200' : 'border-slate-200 bg-white' }}">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 sm:text-base">{{ $option->option_text }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ number_format($optionVotes) }} vote{{ $optionVotes === 1 ? '' : 's' }} &middot; {{ $percentage }}%
                                            @if ($isSelected)
                                                &middot; Your vote
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if ($isOpen)
                                            <form method="POST" action="{{ route('polls.vote', $poll) }}" class="w-full sm:w-auto">
                                                @csrf
                                                <input type="hidden" name="option_id" value="{{ $option->id }}">
                                                <button type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold sm:w-auto {{ $isSelected ? 'border border-blue-300 text-blue-700 hover:bg-blue-100' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                                                    {{ $isSelected ? 'Selected' : 'Vote' }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600">
                                                Voting Closed
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $isSelected ? 'bg-blue-600' : 'bg-cyan-500' }}"
                                         style="width: {{ $barWidth }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
