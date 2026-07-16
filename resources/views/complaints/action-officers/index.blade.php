<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-base-content/90">Action Officers</h2>
            <span class="rounded-full bg-base-200 px-3 py-1 text-xs font-semibold text-base-content/80 badge badge-sm">Reference Data</span>
        </div>
    </x-slot>

    <div class="space-y-5 py-6">
        @include('complaints.references._nav')

        <section class="rounded-2xl bg-gradient-to-br from-slate-900 via-amber-900 to-orange-800 p-6 text-white shadow-lg">
            <h1 class="text-2xl font-bold">Action Officer Directory</h1>
            <p class="mt-2 max-w-3xl text-sm text-amber-100">Create responder accounts, assign each officer to an active department, and retain complaint history when access is removed.</p>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 alert alert-error"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([['Total', 'total'], ['With Department', 'assigned'], ['No Department', 'unassigned'], ['Filtered', 'filtered']] as [$label, $key])
                <div class="rounded-xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/60">{{ $label }}</p>
                    <p class="mt-1 text-xl font-bold text-base-content">{{ number_format($stats[$key] ?? 0) }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
            <form method="GET" action="{{ route('complaints.action-officers.index') }}" class="grid gap-3 sm:grid-cols-3">
                <input name="q" value="{{ request('q') }}" placeholder="Search by name or email" class="rounded-lg border-base-300 text-sm sm:col-span-2 input input-bordered">
                <select name="department_id" class="rounded-lg border-base-300 text-sm select select-bordered">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>@endforeach
                </select>
                <div class="flex gap-2 sm:col-span-3 sm:justify-end">
                    <a href="{{ route('complaints.action-officers.index') }}" class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80">Clear</a>
                    <button class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white btn btn-warning btn-sm">Apply Filters</button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
            <h2 class="font-semibold text-base-content">Create Action Officer</h2>
            <form method="POST" action="{{ route('complaints.action-officers.store') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Full name" required class="rounded-lg border-base-300 text-sm input input-bordered">
                <input name="email" type="email" value="{{ old('email') }}" placeholder="Email address" required class="rounded-lg border-base-300 text-sm input input-bordered">
                <select name="department_id" required class="rounded-lg border-base-300 text-sm select select-bordered">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>@endforeach
                </select>
                <input name="password" type="password" placeholder="Temporary password" required class="rounded-lg border-base-300 text-sm input input-bordered">
                <input name="password_confirmation" type="password" placeholder="Confirm password" required class="rounded-lg border-base-300 text-sm input input-bordered">
                <button class="btn btn-neutral btn-sm">Create Officer</button>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($officers as $officer)
                <article class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm card">
                    <div class="flex items-start justify-between gap-3">
                        <div><h3 class="font-semibold text-base-content">{{ $officer->name }}</h3><p class="text-sm text-base-content/60">{{ $officer->email }}</p></div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 badge badge-sm">{{ $officer->assigned_complaints_count }} cases</span>
                    </div>
                    <p class="mt-2 text-sm text-base-content/70">Department: <strong>{{ $officer->department?->name ?? 'Not assigned' }}</strong></p>

                    <form method="POST" action="{{ route('complaints.action-officers.update', $officer) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $officer->name }}" required class="rounded-lg border-base-300 text-sm">
                        <input name="email" type="email" value="{{ $officer->email }}" required class="rounded-lg border-base-300 text-sm">
                        <select name="department_id" required class="rounded-lg border-base-300 text-sm sm:col-span-2 select select-bordered">
                            <option value="">Select department</option>
                            @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((int) $officer->department_id === (int) $department->id)>{{ $department->name }}</option>@endforeach
                        </select>
                        <input name="password" type="password" placeholder="New password (optional)" class="rounded-lg border-base-300 text-sm input input-bordered">
                        <input name="password_confirmation" type="password" placeholder="Confirm new password" class="rounded-lg border-base-300 text-sm input input-bordered">
                        <div class="sm:col-span-2 flex justify-end"><button class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold text-base-content/80 btn btn-outline btn-sm">Save Officer</button></div>
                    </form>

                    <form method="POST" action="{{ route('complaints.action-officers.destroy', $officer) }}" class="mt-3" onsubmit="return confirm('Remove Action Officer access? The user account and history will be retained.')">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-semibold text-rose-700 btn btn-error btn-xs">Remove Action Officer Access</button>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 p-10 text-center text-sm text-base-content/60 xl:col-span-2 card">No action officers found.</div>
            @endforelse
        </section>

        {{ $officers->links() }}
    </div>
</x-app-layout>
