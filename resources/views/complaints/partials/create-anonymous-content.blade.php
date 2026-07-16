<div class="max-w-5xl mx-auto space-y-5">
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 alert alert-error">
            <p class="font-semibold">Please check your submission details.</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-amber-900 to-orange-800 p-5 text-white shadow-lg sm:p-6">
        <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-base-100/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-amber-300/20 blur-2xl"></div>

        <div class="relative">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200">Anonymous Submission</p>
            <h1 class="mt-2 text-2xl font-bold">Submit Anonymous Complaint</h1>
            <p class="mt-2 max-w-3xl text-sm text-amber-100/90">
                Report a concern without account tracking. You can submit anonymously, but you will not receive notifications or case updates.
            </p>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Daily Limit</p>
            <p class="mt-1 font-semibold">5 submissions per device</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-700">Tracking</p>
            <p class="mt-1 font-semibold">No complaint tracking for anonymous users</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 alert alert-success">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Visibility</p>
            <p class="mt-1 font-semibold">Public summary or private internal case</p>
        </div>
    </section>

    <section class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-6 card">
        <h2 class="text-base font-semibold text-base-content">Complaint Form</h2>
        <p class="mt-1 text-sm text-base-content/70">Provide clear details to help departments respond faster.</p>

        <form method="POST" action="{{ route('complaints.anonymous.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="device_fingerprint" id="device_fingerprint">
            @include('complaints.partials.form-fields', ['isAnonymousForm' => true])

            <div id="similar-complaints" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3">
                <p class="text-sm font-semibold text-amber-800">Similar complaints found</p>
                <p class="mt-1 text-xs text-amber-700">Consider reviewing existing cases before submitting a duplicate.</p>
                <div id="similar-list" class="mt-2 space-y-2"></div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('complaints.public.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 hover:bg-base-200 btn btn-outline btn-sm">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 btn btn-primary btn-sm">
                    Submit Complaint
                </button>
            </div>
        </form>
    </section>
</div>

<script>
    (() => {
        const fingerprintInput = document.getElementById('device_fingerprint');
        const title = document.getElementById('title');
        const summary = document.getElementById('short_summary');
        const wrapper = document.getElementById('similar-complaints');
        const list = document.getElementById('similar-list');
        let timer = null;

        const ensureDeviceFingerprint = () => {
            if (!fingerprintInput) {
                return;
            }

            try {
                const key = 'complaints_anonymous_device_fp_v1';
                let fp = localStorage.getItem(key);
                if (!fp) {
                    fp = (window.crypto && typeof window.crypto.randomUUID === 'function')
                        ? window.crypto.randomUUID()
                        : `fp_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
                    localStorage.setItem(key, fp);
                }

                fingerprintInput.value = fp;
            } catch (error) {
                fingerprintInput.value = '';
            }
        };

        const render = (items) => {
            if (!items.length) {
                wrapper.classList.add('hidden');
                list.innerHTML = '';
                return;
            }

            wrapper.classList.remove('hidden');
            list.innerHTML = items.map((item) => `
                <a href="${item.url}" class="block rounded-lg border border-amber-200 bg-base-100 p-2 text-sm text-amber-900 hover:bg-amber-100">
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
        ensureDeviceFingerprint();
    })();
</script>
