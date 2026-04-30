<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Support</h1>
                <p class="mt-2 text-sm text-slate-600">For app, account, public service, and privacy help.</p>
            </div>

            @if (session('support_status'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800">
                    {{ session('support_status') }}
                </div>
            @endif

            <div class="grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Office Support</h2>
                    <p class="mt-2">{{ $settings['contact_email'] ?? config('mail.from.address', 'support@example.com') }}</p>
                    <p class="mt-1">{{ $settings['contact_phone'] ?? 'Contact your barangay or city office.' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 p-4">
                    <h2 class="font-semibold text-slate-900">Office Address</h2>
                    <p class="mt-2">{{ $settings['office_address'] ?? 'Please contact the local office for assistance.' }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-4 text-sm leading-6 text-slate-700">
                <p>For account access issues, include your resident ID, full name, contact number, and a brief description of the problem.</p>
                <p>For privacy or data deletion requests, use the account deletion page so the request can be tracked with a reference number.</p>
            </div>

            <form method="POST" action="{{ route('support-requests.store') }}" class="mt-8 space-y-5 border-t border-slate-200 pt-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="resident_identifier" class="block text-sm font-semibold text-slate-800">Resident ID</label>
                        <input id="resident_identifier" name="resident_identifier" type="text" value="{{ old('resident_identifier') }}" class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('resident_identifier') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-800">Category</label>
                        <select id="category" name="category" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ([
                                'account' => 'Account access',
                                'privacy' => 'Privacy or data',
                                'technical' => 'Technical issue',
                                'service-request' => 'Service request',
                                'emergency' => 'Emergency feature',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="resident_name" class="block text-sm font-semibold text-slate-800">Full name</label>
                        <input id="resident_name" name="resident_name" type="text" value="{{ old('resident_name') }}" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('resident_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_number" class="block text-sm font-semibold text-slate-800">Contact number</label>
                        <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number') }}" class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('contact_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="subject" class="block text-sm font-semibold text-slate-800">Subject</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-semibold text-slate-800">Message</label>
                    <textarea id="message" name="message" rows="5" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800">
                    Submit support request
                </button>
            </form>

            <div class="mt-8 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-indigo-700">Privacy Policy</a>
                <a href="{{ route('legal.terms') }}" class="font-semibold text-indigo-700">Terms</a>
                <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700">Account deletion</a>
            </div>
        </div>
    </div>
</x-guest-layout>
