<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-base-content/90">{{ $heading }}</h2>
            <span class="rounded-full bg-base-200 px-3 py-1 text-xs font-semibold text-base-content/80 badge badge-sm">Reference Data</span>
        </div>
    </x-slot>

    <div class="space-y-5 py-6">
        @include('complaints.references._nav')

        <section class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-900 to-blue-800 p-6 text-white shadow-lg">
            <h1 class="text-2xl font-bold">{{ $heading }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-blue-100">{{ $description }}</p>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 alert alert-error">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([['Total', 'total'], ['Active', 'active'], ['Inactive', 'inactive'], ['Filtered', 'filtered']] as [$label, $key])
                <div class="rounded-xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">{{ $label }}</p>
                    <p class="mt-1 text-xl font-bold text-base-content">{{ number_format($stats[$key] ?? 0) }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
            <form method="GET" action="{{ route($routePrefix.'.index') }}" class="grid gap-3 sm:grid-cols-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($resourceLabel) }} records" class="rounded-lg border-base-300 text-sm sm:col-span-2 input input-bordered">
                <select name="state" class="rounded-lg border-base-300 text-sm select select-bordered">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('state') === 'active')>Active</option>
                    <option value="inactive" @selected(request('state') === 'inactive')>Inactive</option>
                </select>
                <div class="flex gap-2 sm:col-span-3 sm:justify-end">
                    <a href="{{ route($routePrefix.'.index') }}" class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80">Clear</a>
                    <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white btn btn-primary btn-sm">Apply Filters</button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
            <h2 class="font-semibold text-base-content">Create {{ $resourceLabel }}</h2>
            <form method="POST" action="{{ route($routePrefix.'.store') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @csrf
                @foreach ($fields as $name => $field)
                    <label class="text-xs font-semibold uppercase tracking-wide text-base-content/60">
                        {{ $field['label'] }}
                        <input type="{{ $field['type'] ?? 'text' }}" name="{{ $name }}" value="{{ old($name, $name === 'sort_order' ? 0 : '') }}"
                               placeholder="{{ $field['placeholder'] ?? '' }}" @required($field['required'] ?? false)
                               class="mt-1 block w-full rounded-lg border-base-300 text-sm input input-bordered">
                    </label>
                @endforeach
                <div class="flex items-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-base-content/80"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                    <button class="btn btn-neutral btn-sm">Add {{ $resourceLabel }}</button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($records as $record)
                <article class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-base-content">{{ $record->name }}</h3>
                            <p class="text-xs text-base-content/60">{{ number_format($record->complaints_count ?? $record->alerts_count ?? 0) }} linked {{ strtolower($usageLabel) }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }} badge badge-sm">{{ $record->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <form method="POST" action="{{ route($routePrefix.'.update', $record) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        @method('PUT')
                        @foreach ($fields as $name => $field)
                            <label class="text-xs font-semibold uppercase tracking-wide text-base-content/60">
                                {{ $field['label'] }}
                                <input type="{{ $field['type'] ?? 'text' }}" name="{{ $name }}" value="{{ $record->{$name} }}" @required($field['required'] ?? false)
                                       class="mt-1 block w-full rounded-lg border-base-300 text-sm">
                            </label>
                        @endforeach
                        <div class="flex items-end justify-between gap-3 sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-base-content/80"><input type="checkbox" name="is_active" value="1" @checked($record->is_active)> Active</label>
                            <button class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 btn btn-outline btn-sm">Save</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route($routePrefix.'.destroy', $record) }}" class="mt-3" onsubmit="return confirm('Delete this {{ strtolower($resourceLabel) }}?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-semibold text-rose-700 btn btn-error btn-xs">Delete</button>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 p-10 text-center text-sm text-base-content/60 xl:col-span-2 card">No records found.</div>
            @endforelse
        </section>

        {{ $records->links() }}
    </div>
</x-app-layout>
