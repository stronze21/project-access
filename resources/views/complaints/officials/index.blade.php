<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Public Officials
            </h2>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Reference Data
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl-removed mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-900 to-violet-800 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-indigo-300/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-200">Tag Directory</p>
                        <h3 class="mt-2 text-xl font-bold sm:text-2xl">Public Official Registry and Visibility Control</h3>
                        <p class="mt-2 max-w-2xl text-sm text-indigo-100/90">
                            Manage the list of taggable officials used in complaint workflows and keep designation data current.
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
                    <p class="font-semibold">Please check the form fields.</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) ($stats['total'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Active</p>
                    <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format((int) ($stats['active'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Inactive</p>
                    <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format((int) ($stats['inactive'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Distinct Positions</p>
                    <p class="mt-1 text-xl font-bold text-indigo-700">{{ number_format((int) ($stats['positions'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Filtered Result</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format((int) ($stats['filtered'] ?? 0)) }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Filters</h3>
                        <p class="text-xs text-slate-500">Find officials by name, position, or status.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('complaints.officials.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Clear Filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('complaints.officials.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="q"
                               name="q"
                               type="text"
                               value="{{ request('q') }}"
                               placeholder="Official name or position"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="state" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="state" name="state" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('state') === 'active')>Active</option>
                            <option value="inactive" @selected(request('state') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            Apply Filters
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($activeFilterLabels as $label)
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Add Public Official</h3>
                    <p class="text-xs text-slate-500">Create an official entry that can be tagged in complaint case management.</p>
                </div>
                <form method="POST" action="{{ route('complaints.officials.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <div class="lg:col-span-2">
                        <label for="new_name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                        <input id="new_name"
                               type="text"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="e.g. Maria Santos"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               required>
                    </div>
                    <div>
                        <label for="new_position" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Position</label>
                        <input id="new_position"
                               type="text"
                               name="position"
                               value="{{ old('position') }}"
                               placeholder="e.g. City Engineer"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                               required>
                    </div>
                    <div class="flex flex-col justify-end gap-2">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                            Active
                        </label>
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Add Official
                        </button>
                    </div>
                </form>
            </section>

            <section class="space-y-4 md:hidden">
                @forelse ($officials as $official)
                    @php
                        $statusClass = $official->is_active
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-amber-100 text-amber-800';
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $official->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $official->position }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                {{ $official->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-3 rounded-lg bg-slate-50 px-2.5 py-2 text-xs text-slate-600">
                            <p class="font-semibold text-slate-500">Tagged Cases</p>
                            <p class="mt-0.5 text-slate-700">{{ number_format((int) $official->complaints_count) }}</p>
                        </div>

                        <form method="POST" action="{{ route('complaints.officials.update', $official) }}" class="mt-4 space-y-2">
                            @csrf
                            @method('PUT')
                            <input type="text"
                                   name="name"
                                   value="{{ $official->name }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   required>
                            <input type="text"
                                   name="position"
                                   value="{{ $official->position }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   required>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" name="is_active" value="1" @checked($official->is_active)>
                                Active official
                            </label>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Save
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('complaints.officials.destroy', $official) }}" class="mt-2" onsubmit="return confirm('Delete this official?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                Delete
                            </button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600 shadow-sm">
                        No public officials found.
                    </div>
                @endforelse
            </section>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Official</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Position</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tagged Cases</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Edit</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($officials as $official)
                                @php
                                    $statusClass = $official->is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-800';
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $official->name }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $official->position }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ number_format((int) $official->complaints_count) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $official->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <form method="POST" action="{{ route('complaints.officials.update', $official) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text"
                                                   name="name"
                                                   value="{{ $official->name }}"
                                                   class="block w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                                   required>
                                            <input type="text"
                                                   name="position"
                                                   value="{{ $official->position }}"
                                                   class="block w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                                   required>
                                            <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                                <input type="checkbox" name="is_active" value="1" @checked($official->is_active)>
                                                Active
                                            </label>
                                            <button type="submit"
                                                    class="inline-flex w-fit rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <form method="POST" action="{{ route('complaints.officials.destroy', $official) }}" onsubmit="return confirm('Delete this official?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-600">No public officials found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $officials->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
