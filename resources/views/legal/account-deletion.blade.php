<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Account and Data Deletion Request</h1>
                <p class="mt-2 text-sm text-slate-600">Submit this form to request review of your ProjectAccess resident account and app data.</p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('account-deletion.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="resident_identifier" class="block text-sm font-semibold text-slate-800">Resident ID</label>
                    <input id="resident_identifier" name="resident_identifier" type="text" value="{{ old('resident_identifier') }}" class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('resident_identifier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="resident_name" class="block text-sm font-semibold text-slate-800">Full name</label>
                    <input id="resident_name" name="resident_name" type="text" value="{{ old('resident_name') }}" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('resident_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-800">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="block text-sm font-semibold text-slate-800">Contact number</label>
                        <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number') }}" class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('contact_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-semibold text-slate-800">Reason</label>
                    <textarea id="reason" name="reason" rows="5" class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                    @error('reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="requested_action" class="block text-sm font-semibold text-slate-800">What do you want deleted?</label>
                    <select id="requested_action" name="requested_action" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="delete-account-and-data" @selected(old('requested_action', 'delete-account-and-data') === 'delete-account-and-data')>Delete my ProjectAccess account and app data</option>
                        <option value="delete-app-data-only" @selected(old('requested_action') === 'delete-app-data-only')>Delete app data where possible, but keep my account active</option>
                    </select>
                    @error('requested_action') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Some official transaction records may be retained where required for legal, audit, emergency, or public-service obligations.
                </div>

                <label class="flex gap-3 text-sm leading-6 text-slate-700">
                    <input type="checkbox" name="retention_acknowledged" value="1" required class="mt-1 rounded border-slate-300 text-indigo-700 shadow-sm focus:ring-indigo-500">
                    <span>I understand that official transaction, legal, audit, fraud-prevention, emergency, or public-service records may need to be retained even after my request is processed.</span>
                </label>
                @error('retention_acknowledged') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800">
                    Submit request
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
