<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Complaint {{ $complaint->reference_code }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                You can edit this complaint only until it is assigned.
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('complaints.update', $complaint) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('complaints.partials.form-fields', ['isAnonymousForm' => false])

                    <div id="similar-complaints" class="rounded-md border border-amber-200 bg-amber-50 p-3 hidden">
                        <p class="text-sm font-semibold text-amber-800">Similar complaints found</p>
                        <div id="similar-list" class="mt-2 space-y-2"></div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('complaints.my.index') }}"
                           class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <x-primary-button class="justify-center">
                            Save Changes
                        </x-primary-button>
                    </div>
                </form>
            </div>
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
                    exclude_id: '{{ $complaint->id }}',
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
