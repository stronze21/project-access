<x-guest-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-10">
        <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-indigo-700">ProjectAccess</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Privacy Policy</h1>
                <p class="mt-2 text-sm text-slate-600">Last updated: {{ now()->format('F j, Y') }}</p>
            </div>

            <div class="space-y-5 text-sm leading-6 text-slate-700">
                <p>ProjectAccess helps residents access public services, announcements, aid distribution records, emergency alerts, grievance reporting, and service request tracking.</p>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Information We Collect</h2>
                    <p class="mt-2">We may collect resident profile information, contact details, household information, submitted service requests, grievance reports, emergency SOS details, device notification tokens, uploaded photos or signatures, and technical information needed to secure and operate the service.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">How We Use Information</h2>
                    <p class="mt-2">We use information to verify resident identity, provide local government services, send service and emergency notifications, process aid distribution records, respond to submitted reports, protect accounts, and comply with lawful public-service requirements.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Sharing and Retention</h2>
                    <p class="mt-2">Information is available only to authorized personnel and service providers who support the platform. Records are retained only as long as needed for public-service operations, audit requirements, legal obligations, and resident support.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Resident Choices</h2>
                    <p class="mt-2">Residents may request correction, support, or account/data deletion review through the support and account deletion pages linked below. Some records may need to be retained where required by law or official transaction history.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Account and Data Deletion</h2>
                    <p class="mt-2">Residents can request deletion of their app account and app-related personal data through the in-app Settings screen or the public account deletion page. Requests are reviewed by authorized personnel, and retained records are limited to official transactions, legal obligations, fraud prevention, audit trails, emergency reports, or other public-service records that cannot be deleted immediately.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">Security</h2>
                    <p class="mt-2">ProjectAccess uses authenticated access, role-based administration, secure API tokens, and limited staff permissions to protect resident data. Residents should report suspected unauthorized account access through the support page.</p>
                </section>
            </div>

            <div class="mt-8 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('legal.terms') }}" class="font-semibold text-indigo-700">Terms</a>
                <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-700">Support</a>
                <a href="{{ route('account-deletion.create') }}" class="font-semibold text-indigo-700">Account deletion</a>
            </div>
        </div>
    </div>
</x-guest-layout>
