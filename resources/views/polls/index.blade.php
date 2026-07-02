<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Community Polls</h2>
            @if ($canManagePolls)
                <a href="{{ route('polls.create') }}"
                   class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Create Poll
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-900 to-cyan-700 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-16 h-24 w-24 rounded-full bg-cyan-200/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Citizen Engagement</p>
                        <h3 class="mt-2 text-xl font-bold sm:text-2xl">Vote on City Questions and Priorities</h3>
                        <p class="mt-2 max-w-2xl text-sm text-cyan-100/90">
                            Polls are created by Super Admin, Admin, or Mayor. Every logged-in user can cast one vote per poll.
                        </p>
                    </div>
                </div>
            </section>

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

            <section class="space-y-4">
                @forelse ($polls as $poll)
                    @php
                        $totalVotes = (int) $poll->votes_count;
                        $selectedOptionId = (int) optional($poll->votes->first())->poll_option_id;
                        $isOpen = $poll->isVoteOpen();
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 sm:text-lg">{{ $poll->question }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">
                                        By {{ $poll->creator?->name ?? 'System' }}
                                        • {{ $poll->created_at?->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $isOpen ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $isOpen ? 'Open' : 'Closed' }}
                                    </span>
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-700">
                                        {{ number_format($totalVotes) }} Votes
                                    </span>
                                </div>
                            </div>
                            @if ($poll->description)
                                <p class="mt-2 text-sm text-slate-700">{{ $poll->description }}</p>
                            @endif
                            @if ($poll->ends_at)
                                <p class="mt-1 text-xs text-slate-500">Ends: {{ $poll->ends_at->format('M d, Y h:i A') }}</p>
                            @endif
                        </div>

                        <div class="space-y-3 p-4 sm:p-5">
                            @foreach ($poll->options as $option)
                                @php
                                    $optionVotes = (int) $option->votes_count;
                                    $percentage = $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100, 1) : 0;
                                    $isSelected = $selectedOptionId === (int) $option->id;
                                    $barWidth = $totalVotes > 0 ? max(2, $percentage) : 0;
                                @endphp
                                <div class="rounded-xl border {{ $isSelected ? 'border-blue-300 bg-blue-50/40' : 'border-slate-200 bg-white' }} p-3">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800">{{ $option->option_text }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ number_format($optionVotes) }} vote{{ $optionVotes === 1 ? '' : 's' }}
                                                ({{ $percentage }}%)
                                                @if ($isSelected)
                                                    • Your vote
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if ($isOpen)
                                                <form method="POST" action="{{ route('polls.vote', $poll) }}">
                                                    @csrf
                                                    <input type="hidden" name="option_id" value="{{ $option->id }}">
                                                    <button type="submit"
                                                            class="inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold {{ $isSelected ? 'border border-blue-300 text-blue-700 hover:bg-blue-100' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                                                        {{ $isSelected ? 'Selected' : 'Vote' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                                    Voting Closed
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full {{ $isSelected ? 'bg-blue-600' : 'bg-cyan-500' }}" style="width: {{ $barWidth }}%"></div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="pt-1">
                                <a href="{{ route('polls.show', $poll) }}"
                                   class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    View Poll Details
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">
                        No polls available yet.
                    </div>
                @endforelse
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $polls->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
