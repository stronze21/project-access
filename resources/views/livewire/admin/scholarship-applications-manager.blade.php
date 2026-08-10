<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Scholarship Applications</h1>
            <p class="mt-1 text-sm text-gray-600">Review ACSP digital submissions, request corrections, conditionally approve, and complete physical verification awards.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-mary-button wire:click="$set('activeTab', 'queue')" label="Review queue" class="{{ $activeTab === 'queue' ? 'btn-primary' : 'btn-outline' }}" />
        <x-mary-button wire:click="$set('activeTab', 'programs')" label="Programs & walk-in" class="{{ $activeTab === 'programs' ? 'btn-primary' : 'btn-outline' }}" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-mary-stat title="Submitted" value="{{ number_format($counts['submitted'] ?? 0) }}" icon="o-inbox" class="text-warning" />
        <x-mary-stat title="Under review" value="{{ number_format($counts['under_review'] ?? 0) }}" icon="o-eye" class="text-info" />
        <x-mary-stat title="Conditional" value="{{ number_format($counts['conditionally_approved'] ?? 0) }}" icon="o-check-badge" class="text-success" />
        <x-mary-stat title="Awarded" value="{{ number_format($counts['awarded'] ?? 0) }}" icon="o-academic-cap" class="text-primary" />
    </div>

    @if($activeTab === 'programs')
        <div class="grid gap-6 lg:grid-cols-2">
            <x-mary-card title="{{ $editingProgramId ? 'Edit program' : 'Create / update walk-in settings' }}">
                <div class="space-y-3">
                    <x-mary-input wire:model="programName" label="Program name" />
                    <x-mary-input wire:model="programAcademicYear" label="Academic year" placeholder="2026-2027" />
                    <x-mary-textarea wire:model="programDescription" label="Description" rows="3" />
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-mary-input wire:model="programOpenAt" type="datetime-local" label="Open at" />
                        <x-mary-input wire:model="programCloseAt" type="datetime-local" label="Close at" />
                    </div>
                    <x-mary-input wire:model="programOfficeAddress" label="Office address" />
                    <x-mary-input wire:model="programOfficeHours" label="Office hours" />
                    <x-mary-textarea wire:model="programRequiredOriginals" label="Required originals (one per line)" rows="6" />
                    <x-mary-checkbox wire:model="programIsActive" label="Program is active" />
                    <div class="flex gap-2">
                        <x-mary-button wire:click="saveProgram" class="btn-primary" label="Save program" />
                        <x-mary-button wire:click="resetProgramForm" class="btn-ghost" label="Reset" />
                    </div>
                </div>
            </x-mary-card>

            <div class="space-y-3">
                @foreach($programs as $program)
                    <x-mary-card wire:key="program-{{ $program->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $program->name }}</h3>
                                <p class="text-sm text-slate-600">{{ $program->academic_year }} · {{ $program->is_active ? 'Active' : 'Inactive' }} · {{ $program->isOpen() ? 'Open' : 'Closed' }}</p>
                                <p class="mt-2 text-sm">{{ $program->office_address }}</p>
                                <p class="text-sm text-slate-500">{{ $program->office_hours }}</p>
                            </div>
                            <x-mary-button wire:click="editProgram({{ $program->id }})" class="btn-outline btn-sm" label="Edit" />
                        </div>
                    </x-mary-card>
                @endforeach
            </div>
        </div>
    @else
        <x-mary-card>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search resident, ID, or reference" icon="o-magnifying-glass" />
                <x-mary-select wire:model.live="status" :options="$statusOptions" />
                <x-mary-select wire:model.live="applicantType" :options="$applicantTypeOptions" />
                <x-mary-select wire:model.live="programId" :options="$programOptions" />
            </div>
        </x-mary-card>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_1fr]">
            <div class="space-y-3">
                @forelse($applications as $application)
                    <x-mary-card
                        wire:key="application-{{ $application->id }}"
                        class="cursor-pointer {{ $selectedApplicationId === $application->id ? 'ring-2 ring-primary' : '' }}"
                        wire:click="selectApplication({{ $application->id }})"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ $application->resident?->full_name }}</h3>
                                <p class="text-sm text-slate-600">{{ $application->reference_number }} · {{ $application->resident?->resident_id }}</p>
                                <p class="mt-1 text-sm">{{ $application->program?->name }} · {{ $application->applicant_type === 'new' ? 'New' : 'On-going' }}</p>
                            </div>
                            <span class="badge {{ $application->status === 'awarded' || $application->status === 'conditionally_approved' ? 'badge-success' : ($application->status === 'rejected' ? 'badge-error' : 'badge-warning') }}">
                                {{ $application->statusLabel() }}
                            </span>
                        </div>
                    </x-mary-card>
                @empty
                    <x-mary-card>
                        <p class="text-sm text-slate-500">No applications match the current filters.</p>
                    </x-mary-card>
                @endforelse
                {{ $applications->links() }}
            </div>

            <div>
                @if($selected)
                    <x-mary-card title="Application detail">
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-lg font-semibold">{{ $selected->resident?->full_name }}</h2>
                                <p class="text-sm text-slate-600">{{ $selected->reference_number }} · {{ $selected->resident?->resident_id }}</p>
                                <p class="text-sm">{{ $selected->statusLabel() }} · {{ $selected->applicant_type === 'new' ? 'New Applicant' : 'On-going Scholar' }}</p>
                                @if($selected->rejection_reason)
                                    <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ $selected->rejection_reason }}</p>
                                @endif
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <x-mary-input wire:model="gwa" label="GWA" type="number" step="0.01" min="0" max="100" />
                                <x-mary-select wire:model="awardTier" label="Award tier" :options="$tierOptions" />
                            </div>
                            <x-mary-textarea wire:model="staffNotes" label="Staff notes" rows="3" />

                            <div class="flex flex-wrap gap-2">
                                @if(in_array($selected->status, ['submitted', 'under_review'], true))
                                    <x-mary-button wire:click="markUnderReview" class="btn-outline btn-sm" label="Mark under review" />
                                    <x-mary-button wire:click="openActionModal('conditional')" class="btn-success btn-sm" label="Conditionally approve" />
                                    <x-mary-button wire:click="openActionModal('resubmit')" class="btn-warning btn-sm" label="Request resubmission" />
                                    <x-mary-button wire:click="openActionModal('reject')" class="btn-error btn-sm" label="Reject" />
                                @endif
                                @if($selected->status === 'conditionally_approved')
                                    <x-mary-button wire:click="openActionModal('award')" class="btn-primary btn-sm" label="Physical verify & award" />
                                    <x-mary-button wire:click="openActionModal('resubmit')" class="btn-warning btn-sm" label="Hard copies incomplete" />
                                    <x-mary-button wire:click="openActionModal('reject')" class="btn-error btn-sm" label="Final reject" />
                                @endif
                            </div>

                            <div>
                                <h3 class="mb-2 font-semibold">Documents</h3>
                                <div class="space-y-3">
                                    @foreach($selected->documents as $document)
                                        <div class="rounded-xl border p-3" wire:key="doc-{{ $document->id }}">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <p class="font-medium">{{ $document->documentType?->label }}</p>
                                                    <p class="text-xs text-slate-500">{{ $document->original_name }} · {{ str($document->status)->headline() }} · scan {{ $document->virus_scan_status }}</p>
                                                    @if($document->rejection_reason)
                                                        <p class="text-xs text-error">{{ $document->rejection_reason }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <a class="btn btn-outline btn-xs" href="{{ route('scholarships.documents.media', $document) }}" target="_blank">Download</a>
                                                    <x-mary-button wire:click="verifyDocument({{ $document->id }})" class="btn-success btn-xs" label="Verify" />
                                                    <x-mary-button wire:click="rejectDocument({{ $document->id }})" class="btn-error btn-xs" label="Reject doc" />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <x-mary-textarea class="mt-3" wire:model="actionReason" label="Reason for document rejection / resubmission" rows="2" />
                            </div>

                            <div>
                                <h3 class="mb-2 font-semibold">Timeline</h3>
                                <div class="space-y-2">
                                    @foreach($selected->events as $event)
                                        <div class="rounded-lg bg-slate-50 p-3 text-sm">
                                            <strong>{{ str($event->to_status)->headline() }}</strong>
                                            <p class="text-slate-600">{{ $event->note }}</p>
                                            <p class="text-xs text-slate-400">{{ $event->created_at?->format('M d, Y g:i A') }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </x-mary-card>
                @else
                    <x-mary-card>
                        <p class="text-sm text-slate-500">Select an application from the queue to review documents and take action.</p>
                    </x-mary-card>
                @endif
            </div>
        </div>
    @endif

    <x-mary-modal wire:model="showActionModal" title="Confirm action" class="backdrop-blur">
        <p class="mb-3 text-sm text-slate-600">
            @if($pendingAction === 'conditional') Conditionally approve this application after digital document review.
            @elseif($pendingAction === 'award') Confirm physical hard-copy verification and award the scholarship.
            @elseif($pendingAction === 'resubmit') Request the resident to correct and resubmit documents.
            @elseif($pendingAction === 'reject') Reject this scholarship application.
            @endif
        </p>
        @if(in_array($pendingAction, ['resubmit', 'reject'], true))
            <x-mary-textarea wire:model="actionReason" label="Reason (required)" rows="4" />
        @endif
        <x-slot:actions>
            <x-mary-button label="Cancel" class="btn-ghost" wire:click="$set('showActionModal', false)" />
            <x-mary-button label="Confirm" class="btn-primary" wire:click="confirmAction" />
        </x-slot:actions>
    </x-mary-modal>
</div>
