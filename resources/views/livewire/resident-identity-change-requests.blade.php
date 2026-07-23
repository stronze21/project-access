<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Identity Media Replacement Requests</h1>
            <p class="mt-1 text-sm text-gray-600">Verify resident profile photo and signature replacements before they become part of the official identity record.</p>
        </div>
        <x-mary-button link="{{ route('residents.index') }}" icon="o-arrow-left" label="Back to Residents" class="btn-outline" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-mary-stat title="Pending" value="{{ number_format($counts['pending'] ?? 0) }}" icon="o-clock" class="text-warning" />
        <x-mary-stat title="Approved" value="{{ number_format($counts['approved'] ?? 0) }}" icon="o-check-circle" class="text-success" />
        <x-mary-stat title="Denied" value="{{ number_format($counts['denied'] ?? 0) }}" icon="o-x-circle" class="text-error" />
    </div>

    <x-mary-card>
        <div class="grid gap-3 md:grid-cols-[1fr_180px_180px]">
            <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search resident, ID, or reference" icon="o-magnifying-glass" />
            <x-mary-select wire:model.live="status" :options="[['id'=>'pending','name'=>'Pending'],['id'=>'approved','name'=>'Approved'],['id'=>'denied','name'=>'Denied'],['id'=>'all','name'=>'All statuses']]" />
            <x-mary-select wire:model.live="type" :options="[['id'=>'all','name'=>'All types'],['id'=>'photo','name'=>'Photos'],['id'=>'signature','name'=>'Signatures']]" />
        </div>
    </x-mary-card>

    <div class="space-y-4">
        @forelse($requests as $request)
            <x-mary-card wire:key="identity-request-{{ $request->id }}">
                <div class="grid gap-5 lg:grid-cols-[220px_1fr_auto] lg:items-start">
                    <div class="flex min-h-44 items-center justify-center overflow-hidden rounded-xl border bg-slate-50 p-3">
                        @if($request->type === 'photo' && $request->requested_file_path)
                            <img src="{{ route('residents.identity-change-requests.media', $request) }}" alt="Requested profile photo for {{ $request->resident->full_name }}" class="max-h-56 w-full object-contain">
                        @elseif($request->type === 'signature' && $request->requested_signature)
                            <img src="{{ $request->requested_signature }}" alt="Requested signature for {{ $request->resident->full_name }}" class="max-h-40 w-full object-contain">
                        @else
                            <div class="text-center text-sm text-slate-500"><x-mary-icon name="o-shield-check" class="mx-auto mb-2 h-10 w-10" />Sensitive request media removed after review</div>
                        @endif
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-900">{{ $request->resident->full_name }}</h2>
                            <x-mary-badge value="{{ strtoupper($request->type) }}" class="badge-info badge-sm" />
                            <x-mary-badge value="{{ strtoupper($request->status) }}" class="badge-sm {{ $request->status === 'pending' ? 'badge-warning' : ($request->status === 'approved' ? 'badge-success' : 'badge-error') }}" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $request->resident->resident_id }} · {{ $request->reference_number }} · Submitted {{ $request->created_at->format('M d, Y g:i A') }}</p>
                        <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resident's reason</span>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $request->request_reason }}</p>
                        </div>
                        @if($request->reviewed_at)
                            <p class="mt-3 text-sm text-slate-600">Reviewed by {{ $request->reviewer?->name ?? 'Former staff member' }} on {{ $request->reviewed_at->format('M d, Y g:i A') }}.</p>
                            @if($request->review_reason)<div class="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-800"><strong>Denial reason:</strong> {{ $request->review_reason }}</div>@endif
                        @endif
                    </div>

                    @if($request->status === 'pending')
                        <div class="flex gap-2 lg:flex-col">
                            <x-mary-button wire:click="approve({{ $request->id }})" wire:confirm="Approve this replacement and update the resident's official {{ $request->type }}?" spinner="approve({{ $request->id }})" icon="o-check" label="Approve" class="btn-success" />
                            <x-mary-button wire:click="openDenyModal({{ $request->id }})" icon="o-x-mark" label="Deny" class="btn-error btn-outline" />
                        </div>
                    @endif
                </div>
            </x-mary-card>
        @empty
            <x-mary-card><div class="py-12 text-center text-slate-500"><x-mary-icon name="o-inbox" class="mx-auto mb-3 h-12 w-12" /><p class="font-medium">No matching replacement requests</p><p class="mt-1 text-sm">New resident submissions will appear here for verification.</p></div></x-mary-card>
        @endforelse
    </div>

    {{ $requests->links() }}

    <x-mary-modal wire:model="showDenyModal" title="Deny replacement request" separator>
        <p class="mb-4 text-sm text-slate-600">Give the resident a clear reason. The proposed media will be securely removed when the request is denied.</p>
        <x-mary-textarea wire:model="denialReason" label="Reason for denial" rows="5" maxlength="2000" required />
        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showDenyModal = false" />
            <x-mary-button wire:click="deny" spinner="deny" label="Deny request" class="btn-error" />
        </x-slot:actions>
    </x-mary-modal>
</div>
