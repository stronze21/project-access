<div>
    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 mb-6 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Resident Details</h1>
            <p class="mt-1 text-sm text-gray-600">View and manage resident information</p>
        </div>
        <div class="flex space-x-2">
            @role('system-administrator')
                <x-mary-button icon="o-identification" link="{{ route('residents.id-card', $resident->id) }}" external="true"
                    label="Print ID" />
            @endrole

            <x-mary-button link="{{ route('residents.edit', $resident->id) }}" icon="o-pencil" label="Edit" />
            <x-mary-button link="{{ route('residents.index') }}"
                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left" label="Back to List" />
        </div>
    </div>

    <!-- Resident Information -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Main Info Card -->
        <div class="lg:col-span-2">
            <x-mary-card>
                <div class="flex flex-col gap-6 md:flex-row md:items-start">
                    <!-- Photo -->
                    <div class="flex-none">
                        @if ($resident->photo_path)
                            <img src="{{ Storage::url($resident->photo_path) }}" alt="{{ $resident->full_name }}"
                                class="object-cover w-32 h-32 rounded-lg">
                        @else
                            <div class="flex items-center justify-center w-32 h-32 rounded-lg bg-base-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        @endif

                        <!-- QR Code Button -->
                        <div class="mt-2">
                            <x-mary-button wire:click="toggleQrCode"
                                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" class="w-full"
                                icon="o-{{ $showQrCode ? 'x-circle' : 'qr-code' }}">
                                {{ $showQrCode ? 'Hide QR' : 'Show QR' }}
                            </x-mary-button>
                        </div>

                        <!-- QR Code Display -->
                        @if ($showQrCode)
                            <div class="mt-2">
                                <img src="{{ route('qrcode.resident', $resident->id) }}" alt="QR Code"
                                    class="w-32 h-32">
                                <div class="mt-1 text-center">
                                    <a href="{{ route('qrcode.download', $resident->id) }}"
                                        class="text-xs text-blue-600 hover:underline">Download</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <h2 class="text-xl font-semibold">{{ $resident->full_name }}</h2>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resident->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $resident->is_active ? 'Active' : 'Inactive' }}
                            </span>

                            @if ($resident->is_senior_citizen)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Senior Citizen
                                </span>
                            @endif

                            @if ($resident->is_pwd)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    PWD
                                </span>
                            @endif

                            @if ($resident->is_solo_parent)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    Solo Parent
                                </span>
                            @endif

                            @if ($resident->is_4ps)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    4Ps
                                </span>
                            @endif

                            @if ($resident->special_sector)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $resident->special_sector }}
                                </span>
                            @endif
                        </div>

                        <p class="mb-4 text-sm text-gray-600">ID: {{ $resident->resident_id }}</p>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Personal Information</h3>
                                <dl class="mt-2 text-sm">
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Birth Date:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->birth_date->format('M d, Y') }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Birthplace:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->birthplace ?: 'Not specified' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Blood Type:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->blood_type ?: 'Not specified' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Age:</dt>
                                        <dd class="font-medium text-gray-900">{{ $resident->getAge() }} years</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Gender:</dt>
                                        <dd class="font-medium text-gray-900">{{ ucfirst($resident->gender) }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Civil Status:</dt>
                                        <dd class="font-medium text-gray-900">{{ ucfirst($resident->civil_status) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Occupation:</dt>
                                        <dd class="font-medium text-gray-900">{{ $resident->occupation ?: 'N/A' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Monthly Income:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->monthly_income ? '₱ ' . number_format($resident->monthly_income, 2) : 'N/A' }}
                                        </dd>
                                    </div>
                                    @if ($resident->precinct_no)
                                        <div class="flex justify-between py-1">
                                            <dt class="text-gray-600">Precinct:</dt>
                                            <dd class="font-medium text-gray-900">
                                                {{ $resident->precinct_no }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Contact Information</h3>
                                <dl class="mt-2 text-sm">
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Contact Number:</dt>
                                        <dd class="font-medium text-gray-900">{{ $resident->contact_number ?: 'N/A' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Email:</dt>
                                        <dd class="font-medium text-gray-900">{{ $resident->email ?: 'N/A' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">RFID Number:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->rfid_number ?: 'Not Assigned' }}</dd>
                                    </div>
                                </dl>

                                @if ($resident->emergency_contact_name || $resident->emergency_contact_number)
                                    <h3 class="mt-4 text-sm font-medium text-gray-500">Emergency Contact</h3>
                                    <dl class="mt-2 text-sm">
                                        @if ($resident->emergency_contact_name)
                                            <div class="flex justify-between py-1">
                                                <dt class="text-gray-600">Name:</dt>
                                                <dd class="font-medium text-gray-900">
                                                    {{ $resident->emergency_contact_name }}</dd>
                                            </div>
                                        @endif

                                        @if ($resident->emergency_contact_relationship)
                                            <div class="flex justify-between py-1">
                                                <dt class="text-gray-600">Relationship:</dt>
                                                <dd class="font-medium text-gray-900">
                                                    {{ ucfirst($resident->emergency_contact_relationship) }}</dd>
                                            </div>
                                        @endif

                                        @if ($resident->emergency_contact_number)
                                            <div class="flex justify-between py-1">
                                                <dt class="text-gray-600">Contact Number:</dt>
                                                <dd class="font-medium text-gray-900">
                                                    {{ $resident->emergency_contact_number }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                @endif

                                <h3 class="mt-4 text-sm font-medium text-gray-500">ID Information</h3>
                                <dl class="mt-2 text-sm">
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Date Issued:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->date_issue ? $resident->date_issue->format('M d, Y') : 'Not issued' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Special Sector:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->special_sector ?: 'None' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Registered On:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->created_at->format('M d, Y') }}</dd>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <dt class="text-gray-600">Last Updated:</dt>
                                        <dd class="font-medium text-gray-900">
                                            {{ $resident->updated_at->format('M d, Y') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signature Display -->
                @if ($resident->signature)
                    <div class="pt-4 mt-6 border-t">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Resident Signature</h3>
                        <div class="p-2 bg-white border rounded">
                            <img src="{{ str_starts_with($resident->signature, 'data:') ? $resident->signature : asset($resident->signature) }}"
                                alt="Resident Signature" class="max-h-20">
                        </div>
                    </div>
                @endif

                <!-- Status Flags -->
                <div class="pt-4 mt-6 border-t">
                    <h3 class="mb-2 text-sm font-medium text-gray-500">Status Indicators</h3>
                    <div class="flex flex-wrap gap-2">

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_pwd ? 'PWD' : 'Not PWD' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_senior_citizen ? 'Senior Citizen' : 'Not Senior' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_solo_parent ? 'Solo Parent' : 'Not Solo Parent' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_pregnant ? 'Pregnant' : 'Not Pregnant' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_lactating ? 'Lactating' : 'Not Lactating' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_indigenous ? 'Indigenous' : 'Not Indigenous' }}
                        </x-mary-badge>

                        <x-mary-badge class="badge-{{ $resident->is_pwd ? 'info' : 'gray' }}">
                            {{ $resident->is_registered_voter ? 'Registered Voter' : 'Not a Voter' }}
                        </x-mary-badge>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4 mt-6 border-t">
                    @can('configure-system')
                        <div>
                            <x-mary-button
                                wire:click="setResidentStatus('{{ $resident->is_active ? 'inactive' : 'active' }}')"
                                class="{{ $resident->is_active ? 'btn-error' : 'btn-success' }}">
                                {{ $resident->is_active ? 'Mark as Inactive' : 'Mark as Active' }}
                            </x-mary-button>
                        </div>
                    @endcan
                    <div>
                        <x-mary-button link="{{ route('distributions.create') }}?resident={{ $resident->id }}"
                            class="tagged-color btn-primary" icon="o-banknotes">
                            Distribute Aid
                        </x-mary-button>
                    </div>
                </div>
            </x-mary-card>
        </div>

        <!-- Household Info Card -->
        <div>
            <x-mary-card title="Household Information">
                @if ($resident->household)
                    <div class="mb-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-medium">{{ $resident->household->household_id }}</h3>
                                <p class="text-sm text-gray-600">
                                    {{ $resident->relationship_to_head ? ucfirst(str_replace('_', ' ', $resident->relationship_to_head)) : 'Member' }}
                                </p>
                            </div>
                            <x-mary-button link="{{ route('households.show', $resident->household_id) }}"
                                size="sm" class="tagged-color btn-secondary btn-outline btn-secline">
                                View
                            </x-mary-button>
                        </div>
                    </div>

                    <div class="space-y-1 text-sm">
                        <p><span class="font-medium">Address:</span> {{ $resident->household->address }}</p>
                        <p><span class="font-medium">Barangay:</span> {{ $resident->household->barangay }}</p>
                        <p><span class="font-medium">City/Municipality:</span>
                            {{ $resident->household->city_municipality }}</p>
                        <p><span class="font-medium">Province:</span> {{ $resident->household->province }}</p>
                        <p><span class="font-medium">Members:</span> {{ $resident->household->member_count }}</p>
                        <p><span class="font-medium">Monthly Income:</span>
                            {{ $resident->household->monthly_income ? '₱ ' . number_format($resident->household->monthly_income, 2) : 'N/A' }}
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <h4 class="mb-2 text-sm font-medium">Other Household Members</h4>
                        <ul class="space-y-2">
                            @foreach ($resident->household->residents->where('id', '!=', $resident->id) as $member)
                                <li>
                                    <a href="{{ route('residents.show', $member->id) }}"
                                        class="flex items-center p-2 text-sm rounded hover:bg-base-50">
                                        @if ($member->photo_path)
                                            <img src="{{ Storage::url($member->photo_path) }}"
                                                alt="{{ $member->full_name }}"
                                                class="object-cover w-6 h-6 mr-2 rounded-full">
                                        @else
                                            <div
                                                class="flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-base-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <span>{{ $member->full_name }}</span>
                                        @if ($member->relationship_to_head === 'head')
                                            <span
                                                class="ml-auto text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Head</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="py-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <p class="mb-2 text-gray-500">No household assigned</p>
                        <x-mary-button link="{{ route('residents.edit', $resident->id) }}" size="sm"
                            class="tagged-color btn-primary">
                            Assign to Household
                        </x-mary-button>
                    </div>
                @endif
            </x-mary-card>
        </div>
    </div>

    <!-- Portal Account Section -->
    <x-mary-card class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Resident Portal Account</h2>
                <p class="text-sm text-gray-600">Manage access to the resident mobile portal</p>
            </div>
            @can('edit-residents')
                <x-mary-button icon="o-cog-6-tooth" wire:click="openPortalAccountModal" class="btn-sm btn-primary">
                    Manage Portal Access
                </x-mary-button>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Email Status -->
            <div class="p-4 border rounded-lg bg-base-50">
                <div class="flex items-center space-x-2">
                    <x-mary-icon name="o-envelope" class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Email Address</span>
                </div>
                <p class="mt-2 text-base font-semibold text-gray-900">
                    {{ $resident->email ?? 'Not Set' }}
                </p>
            </div>

            <!-- Portal Access Status -->
            <div class="p-4 border rounded-lg bg-base-50">
                <div class="flex items-center space-x-2">
                    <x-mary-icon name="o-shield-check" class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Portal Access</span>
                </div>
                <div class="mt-2">
                    @if ($resident->email && $resident->password)
                        <x-mary-badge value="Enabled" class="badge-success" />
                    @else
                        <x-mary-badge value="Disabled" class="badge-ghost" />
                    @endif
                </div>
            </div>

            <!-- Last Login -->
            <div class="p-4 border rounded-lg bg-base-50">
                <div class="flex items-center space-x-2">
                    <x-mary-icon name="o-clock" class="w-5 h-5 text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">Last Login</span>
                </div>
                <p class="mt-2 text-base font-semibold text-gray-900">
                    {{ $resident->last_login_at ? $resident->last_login_at->diffForHumans() : 'Never' }}
                </p>
            </div>
        </div>
    </x-mary-card>

    <!-- Aid Distribution History -->
    <x-mary-card title="Aid Distribution History" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">Reference #</th>
                        <th scope="col" class="px-4 py-3">Program</th>
                        <th scope="col" class="px-4 py-3">Date</th>
                        <th scope="col" class="px-4 py-3">Amount</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($distributions as $distribution)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $distribution->reference_number }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $distribution->ayudaProgram->name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $distribution->distribution_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                ₱{{ number_format($distribution->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $distribution->status === 'distributed'
                                        ? 'bg-green-100 text-green-800'
                                        : ($distribution->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($distribution->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-mary-button link="{{ route('distributions.show', $distribution->id) }}"
                                    size="xs" class="tagged-color btn-secondary btn-outline btn-secline">
                                    Details
                                </x-mary-button>
                            </td>
                        </tr>
                    @endforeach

                    @if ($distributions->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                No aid distributions found for this resident
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($distributions->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $distributions->links() }}
            </div>
        @endif

        <div class="flex justify-end mt-4">
            <x-mary-button link="{{ route('distributions.create') }}?resident={{ $resident->id }}"
                class="tagged-color btn-primary" icon="o-banknotes">
                New Distribution
            </x-mary-button>
        </div>
    </x-mary-card>

    <!-- Notes Section -->
    @if ($resident->notes)
        <x-mary-card title="Notes" class="mb-6">
            <div class="prose-sm prose max-w-none">
                {{ $resident->notes }}
            </div>
        </x-mary-card>
    @endif


    <!-- Portal Account Management Modal -->
    <x-mary-modal wire:model="showPortalAccountModal" title="Manage Portal Account" box-class="max-w-lg">
        <form wire:submit.prevent="savePortalAccount">
            <div class="space-y-4">
                <x-mary-input label="Portal Email" wire:model="portalEmail" type="email"
                    placeholder="Enter email address" required error="{{ $errors->first('portalEmail') }}"
                    hint="This will be used for mobile app login" />

                <div class="p-4 border rounded-lg bg-base-50">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="resetPassword" class="checkbox checkbox-primary">
                        <span class="font-medium label-text">Reset Password</span>
                    </label>
                </div>

                @if ($resetPassword)
                    <div class="space-y-3">
                        <x-mary-input label="New Password" wire:model="portalPassword" type="password"
                            placeholder="Enter new password" required error="{{ $errors->first('portalPassword') }}"
                            hint="Minimum 8 characters" />

                        <x-mary-input label="Confirm Password" wire:model="portalPasswordConfirmation" type="password"
                            placeholder="Confirm new password" required
                            error="{{ $errors->first('portalPasswordConfirmation') }}" />
                    </div>
                @endif

                @if ($resident->email && $resident->password)
                    <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
                        <div class="flex items-start space-x-2">
                            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-yellow-600" />
                            <div>
                                <p class="text-sm font-medium text-yellow-800">Portal Access Currently Enabled</p>
                                <p class="text-xs text-yellow-700">The resident can currently access the mobile portal</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-between mt-6">
                <div>
                    @if ($resident->email && $resident->password)
                        <x-mary-button type="button" wire:click="disablePortalAccess"
                            wire:confirm="Are you sure you want to disable portal access?" class="btn-error btn-outline">
                            Disable Portal Access
                        </x-mary-button>
                    @endif
                </div>
                <div class="flex space-x-2">
                    <x-mary-button type="button" wire:click="$set('showPortalAccountModal', false)"
                        class="btn-secondary btn-outline">
                        Cancel
                    </x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">
                        Save Changes
                    </x-mary-button>
                </div>
            </div>
        </form>
    </x-mary-modal>
</div>
