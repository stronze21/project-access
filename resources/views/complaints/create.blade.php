<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Submit Complaint
            </h2>
            <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                Verified Citizen
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please review your submission details.</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Citizen Ticket Intake</p>
                    <h3 class="mt-2 text-2xl font-bold">Create a New Ticket</h3>
                    <p class="mt-2 max-w-3xl text-sm text-cyan-100/90">
                        Submit clear details so departments can assign faster, act earlier, and close your ticket with better outcomes.
                    </p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">Daily Limit</p>
                    <p class="mt-1 font-semibold">{{ (int) config('complaints.submission_limits.citizen_daily', 5) }} complaints per day</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Visibility</p>
                    <p class="mt-1 font-semibold">Choose named, hidden identity, or private case</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Duplicate Check</p>
                    <p class="mt-1 font-semibold">Similar issues are suggested before submit</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900">Complaint Form</h3>
                    <p class="mt-1 text-sm text-slate-600">Fields marked required should be complete and specific.</p>

                    <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        @include('complaints.partials.form-fields', ['isAnonymousForm' => false])

                        <div id="similar-complaints" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <p class="text-sm font-semibold text-amber-800">Similar complaints found</p>
                            <p class="mt-1 text-xs text-amber-700">You can support an existing complaint instead of submitting a duplicate.</p>
                            <div id="similar-list" class="mt-2 space-y-2"></div>
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <a href="{{ route('complaints.my.index') }}"
                               class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Submit Complaint
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="space-y-4">
                    <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4 shadow-sm sm:p-5">
                        <h4 class="text-sm font-semibold text-cyan-900">Need Faster Submission?</h4>
                        <p class="mt-2 text-sm text-cyan-800">
                            Use the 3-step quick ticket flow: photo, short details, submit.
                        </p>
                        <a href="{{ route('complaints.quick.create') }}"
                           class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-cyan-600 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-700">
                            Open Quick Ticket
                        </a>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <h4 class="text-sm font-semibold text-slate-900">Submission Checklist</h4>
                        <ul class="mt-3 space-y-2 text-sm text-slate-700">
                            <li class="rounded-lg bg-slate-50 px-3 py-2">Use a specific title and concise short summary.</li>
                            <li class="rounded-lg bg-slate-50 px-3 py-2">Describe location details for faster field action.</li>
                            <li class="rounded-lg bg-slate-50 px-3 py-2">Upload clear files if evidence is available.</li>
                            <li class="rounded-lg bg-slate-50 px-3 py-2">Pick the correct visibility option before submit.</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <h4 class="text-sm font-semibold text-slate-900">Need Anonymous Mode?</h4>
                        <p class="mt-2 text-sm text-slate-600">
                            If you prefer not to use your account identity, switch to anonymous submission.
                        </p>
                        <a href="{{ route('complaints.anonymous.create') }}"
                           class="mt-3 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Open Anonymous Form
                        </a>
                    </div>
                </aside>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const title = document.getElementById('title');
            const summary = document.getElementById('short_summary');
            const wrapper = document.getElementById('similar-complaints');
            const list = document.getElementById('similar-list');
            let timer = null;

            const render = (items) => {
                if (!items.length) {
                    wrapper.classList.add('hidden');
                    list.innerHTML = '';
                    return;
                }

                wrapper.classList.remove('hidden');
                list.innerHTML = items.map((item) => `
                    <a href="${item.url}" class="block rounded border border-amber-200 bg-white p-2 text-sm text-amber-900 hover:bg-amber-100">
                        <div class="font-semibold">${item.title}</div>
                        <div class="text-xs text-amber-700">Ref: ${item.reference_code} | Status: ${item.status.replace('_', ' ')} | Supports: ${item.support_count}</div>
                    </a>
                `).join('');
            };

            const loadSimilar = () => {
                const titleValue = title.value.trim();
                if (!titleValue) {
                    render([]);
                    return;
                }

                const params = new URLSearchParams({
                    title: titleValue,
                    short_summary: summary.value.trim(),
                });

                fetch(`{{ route('complaints.similar') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then((payload) => render(payload.data || []))
                    .catch(() => render([]));
            };

            const debounced = () => {
                clearTimeout(timer);
                timer = setTimeout(loadSimilar, 350);
            };

            title.addEventListener('input', debounced);
            summary.addEventListener('input', debounced);
        })();
    </script>
</x-app-layout>
