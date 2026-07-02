<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Departments
            </h2>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Reference Data
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-blue-900 to-cyan-900 p-5 text-white shadow-lg sm:p-6">
                <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-8 left-20 h-24 w-24 rounded-full bg-cyan-300/20 blur-2xl"></div>

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">Operations Directory</p>
                        <h3 class="mt-2 text-xl font-bold sm:text-2xl">Department Registry and Contact Setup</h3>
                        <p class="mt-2 max-w-2xl text-sm text-cyan-100/90">
                            Maintain active departments, update contact information, and monitor linked users and assigned complaints.
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

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
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
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">With Email</p>
                    <p class="mt-1 text-xl font-bold text-blue-700">{{ number_format((int) ($stats['with_email'] ?? 0)) }}</p>
                </div>
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-100 sm:p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">With Description</p>
                    <p class="mt-1 text-xl font-bold text-cyan-700">{{ number_format((int) ($stats['with_description'] ?? 0)) }}</p>
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
                        <p class="text-xs text-slate-500">Find departments by name, email, or status.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <a href="{{ route('complaints.departments.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Clear Filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('complaints.departments.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="q" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input id="q"
                               name="q"
                               type="text"
                               value="{{ request('q') }}"
                               placeholder="Department name, email, or description"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm placeholder:text-slate-400 focus:border-cyan-500 focus:ring-cyan-500">
                    </div>
                    <div>
                        <label for="state" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="state" name="state" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('state') === 'active')>Active</option>
                            <option value="inactive" @selected(request('state') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-700">
                            Apply Filters
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($activeFilterLabels as $label)
                            <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Create Department</h3>
                    <p class="text-xs text-slate-500">Add a department for assignment and routing workflows.</p>
                </div>

                <form method="POST" action="{{ route('complaints.departments.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @csrf
                    <div class="lg:col-span-2">
                        <label for="new_name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                        <input id="new_name"
                               type="text"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="e.g. Public Works"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                               required>
                    </div>
                    <div>
                        <label for="new_email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                        <input id="new_email"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="dept@example.gov"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="new_description" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</label>
                        <input id="new_description"
                               type="text"
                               name="description"
                               value="{{ old('description') }}"
                               placeholder="Short description"
                               class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                    </div>
                    <div class="flex flex-col justify-end gap-2 lg:col-span-1">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                            Active
                        </label>
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Add Department
                        </button>
                    </div>
                </form>
            </section>

            <section class="space-y-4 md:hidden">
                @forelse ($departments as $department)
                    @php
                        $statusClass = $department->is_active
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-amber-100 text-amber-800';
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $department->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $department->email ?: 'No department email' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                {{ $department->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Users</p>
                                <p class="mt-0.5 text-slate-700">{{ number_format((int) $department->users_count) }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-2.5 py-2">
                                <p class="font-semibold text-slate-500">Assigned Cases</p>
                                <p class="mt-0.5 text-slate-700">{{ number_format((int) $department->assigned_complaints_count) }}</p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-600">
                            {{ $department->description ?: 'No description provided.' }}
                        </p>

                        <form method="POST" action="{{ route('complaints.departments.update', $department) }}" class="mt-4 space-y-2">
                            @csrf
                            @method('PUT')

                            <input type="text"
                                   name="name"
                                   value="{{ $department->name }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                                   required>
                            <input type="email"
                                   name="email"
                                   value="{{ $department->email }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                                   placeholder="Email">
                            <input type="text"
                                   name="description"
                                   value="{{ $department->description }}"
                                   class="block w-full rounded-lg border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                                   placeholder="Description">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <input type="checkbox" name="is_active" value="1" @checked($department->is_active)>
                                Active department
                            </label>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        class="inline-flex rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Save
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('complaints.departments.destroy', $department) }}" class="mt-2" onsubmit="return confirm('Delete this department?')">
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
                        No departments found.
                    </div>
                @endforelse
            </section>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Linked Data</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Edit</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($departments as $department)
                                @php
                                    $statusClass = $department->is_active
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-800';
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-sm">
                                        <p class="font-semibold text-slate-900">{{ $department->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $department->description ?: 'No description provided.' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $department->email ?: 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <p class="text-xs text-slate-500">Users: <span class="font-semibold text-slate-700">{{ number_format((int) $department->users_count) }}</span></p>
                                        <p class="text-xs text-slate-500">Assigned cases: <span class="font-semibold text-slate-700">{{ number_format((int) $department->assigned_complaints_count) }}</span></p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <form method="POST" action="{{ route('complaints.departments.update', $department) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text"
                                                   name="name"
                                                   value="{{ $department->name }}"
                                                   class="block w-full rounded-lg border-slate-300 text-xs focus:border-cyan-500 focus:ring-cyan-500"
                                                   required>
                                            <input type="email"
                                                   name="email"
                                                   value="{{ $department->email }}"
                                                   class="block w-full rounded-lg border-slate-300 text-xs focus:border-cyan-500 focus:ring-cyan-500"
                                                   placeholder="Email">
                                            <input type="text"
                                                   name="description"
                                                   value="{{ $department->description }}"
                                                   class="block w-full rounded-lg border-slate-300 text-xs focus:border-cyan-500 focus:ring-cyan-500"
                                                   placeholder="Description">
                                            <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                                <input type="checkbox" name="is_active" value="1" @checked($department->is_active)>
                                                Active
                                            </label>
                                            <button type="submit"
                                                    class="inline-flex w-fit rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <form method="POST" action="{{ route('complaints.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
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
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-600">No departments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                {{ $departments->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
