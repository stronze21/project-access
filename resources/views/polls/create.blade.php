<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-base-content/90 leading-tight">Create Poll</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('polls.index') }}"
                   class="inline-flex items-center rounded-lg border border-base-300 px-3 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                    Back to Polls
                </a>
                <a href="{{ route('polls.index') }}"
                   class="btn btn-neutral btn-sm">
                    View Live Polls
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-900 to-cyan-700 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-base-100/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 left-14 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Engagement Tool</p>
                        <h3 class="mt-2 text-xl font-bold sm:text-2xl">Launch a Clear, Fair Public Poll</h3>
                        <p class="mt-2 max-w-2xl text-sm text-cyan-100/90">
                            Ask one focused question, provide distinct choices, and set a clear end time.
                            Voters can change their vote while the poll remains open.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <span class="rounded-full border border-white/25 bg-base-100/10 px-2.5 py-1 font-semibold backdrop-blur badge badge-sm">2-10 options</span>
                        <span class="rounded-full border border-white/25 bg-base-100/10 px-2.5 py-1 font-semibold backdrop-blur badge badge-sm">One vote per user</span>
                    </div>
                </div>
            </section>

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 alert alert-error">
                    <p class="font-semibold">Please check the form fields.</p>
                    @foreach ($errors->all() as $error)
                        <p class="mt-1">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 lg:col-span-2 card">
                    <form method="POST" action="{{ route('polls.store') }}" class="space-y-5">
                        @csrf

                        <div class="rounded-xl border border-base-300 bg-base-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Step 1</p>
                            <h4 class="mt-1 text-sm font-semibold text-base-content">Write the poll prompt</h4>

                            <div class="mt-3">
                                <label for="question" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Question</label>
                                <input id="question"
                                       name="question"
                                       type="text"
                                       value="{{ old('question') }}"
                                       placeholder="What project should be prioritized next quarter?"
                                       class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-cyan-600 focus:ring-cyan-600 input input-bordered"
                                       required>
                            </div>

                            <div class="mt-3">
                                <label for="description" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Description (Optional)</label>
                                <textarea id="description"
                                          name="description"
                                          rows="3"
                                          placeholder="Add context so users understand what they are voting for."
                                          class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-cyan-600 focus:ring-cyan-600 textarea textarea-bordered">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="rounded-xl border border-base-300 bg-base-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Step 2</p>
                            <h4 class="mt-1 text-sm font-semibold text-base-content">Add response options</h4>

                            <div class="mt-3">
                                <label for="options_text" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Options (One Per Line)</label>
                                <textarea id="options_text"
                                          name="options_text"
                                          rows="9"
                                          placeholder="Road repairs&#10;Street lighting&#10;Drainage system&#10;Public safety patrol"
                                          class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-cyan-600 focus:ring-cyan-600 textarea textarea-bordered"
                                          required>{{ old('options_text') }}</textarea>
                                <p class="mt-1 text-xs text-base-content/60">Minimum 2 options, maximum 10 options. Keep choices short and distinct.</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-base-300 bg-base-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/60">Step 3</p>
                            <h4 class="mt-1 text-sm font-semibold text-base-content">Set poll availability</h4>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="ends_at" class="text-xs font-semibold uppercase tracking-wide text-base-content/60">End Date/Time (Optional)</label>
                                    <input id="ends_at"
                                           name="ends_at"
                                           type="datetime-local"
                                           value="{{ old('ends_at') }}"
                                           class="mt-1 block w-full rounded-lg border-base-300 text-sm focus:border-cyan-600 focus:ring-cyan-600 input input-bordered">
                                </div>
                                <div class="flex items-end">
                                    <label class="inline-flex w-full items-center gap-2 rounded-lg border border-base-300 bg-base-100 px-3 py-2 text-sm text-base-content/80">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                                        Poll is active immediately
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 btn btn-primary btn-sm">
                                Publish Poll
                            </button>
                            <a href="{{ route('polls.index') }}"
                               class="inline-flex items-center rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                                Cancel
                            </a>
                        </div>
                    </form>
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5 card">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-base-content/80">Publishing Checklist</h4>
                        <div class="mt-3 space-y-2 text-sm text-base-content/70">
                            <p class="rounded-lg bg-base-200 px-3 py-2">1. Ask one clear question.</p>
                            <p class="rounded-lg bg-base-200 px-3 py-2">2. Add at least 2 options.</p>
                            <p class="rounded-lg bg-base-200 px-3 py-2">3. Avoid overlapping options.</p>
                            <p class="rounded-lg bg-base-200 px-3 py-2">4. Set an end time for time-bound polls.</p>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4 shadow-sm sm:p-5">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-cyan-800">Preview Behavior</h4>
                        <p class="mt-2 text-sm text-cyan-900/90">
                            Users can vote once per poll and can change their vote while the poll is open.
                            Once closed, results remain visible.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
