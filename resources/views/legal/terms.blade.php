<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Terms of Use</h1>
                <p class="mt-2 text-sm text-slate-600">Last updated: {{ now()->format('F j, Y') }}</p>
            </div>

            <div class="space-y-5 text-sm leading-6 text-slate-700">
                <p>By using ProjectAccess, residents agree to use the platform only for lawful public-service purposes and to provide accurate information when submitting requests, reports, or emergency alerts.</p>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Resident Accounts</h2>
                    <p class="mt-2">Residents are responsible for keeping their login credentials secure and for notifying the barangay or city office if they suspect unauthorized access.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Submitted Information</h2>
                    <p class="mt-2">Service requests, grievance reports, and SOS alerts must be truthful and related to legitimate public-service needs. Misuse may result in account restrictions or referral to the proper office.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Availability</h2>
                    <p class="mt-2">ProjectAccess may be updated, temporarily unavailable, or limited during maintenance, connectivity issues, or emergency system work.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Support</h2>
                    <p class="mt-2">Residents can use the support page to ask for help with account access, data corrections, service records, or privacy concerns.</p>
                </section>
            </div>

            <div class="mt-8 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-indigo-700">Privacy Policy</a>
                <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700">Support</a>
                <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700">Account deletion</a>
            </div>
        </div>
    </div>
</x-guest-layout>
