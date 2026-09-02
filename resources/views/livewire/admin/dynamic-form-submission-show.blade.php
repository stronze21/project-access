<div>
    <div class="mb-6">
        <a href="{{ route('forms.inbox', ['formId' => $submission->dynamic_form_id]) }}" class="text-sm text-slate-500 hover:text-slate-800">← Inbox</a>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $submission->reference_number }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $submission->form?->title }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($submission->status)
                    <span class="badge {{ $submission->status->badgeClass() }}">{{ $submission->status->label }}</span>
                @endif
                @if ($canEditDraft)
                    <a class="btn btn-outline btn-sm h-8 min-h-8" href="{{ route('forms.fill', ['form' => $submission->form, 'submission' => $submission->id]) }}">Edit draft</a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-mary-card title="Answers">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ($submission->schemaFields() as $field)
                        @include('livewire.admin.dynamic-forms.fields.display', [
                            'field' => $field,
                            'answers' => $submission->answers ?? [],
                            'submission' => $submission,
                        ])
                    @endforeach
                </div>
            </x-mary-card>

            <x-mary-card title="History">
                <div class="space-y-3">
                    @forelse ($submission->events as $event)
                        <div class="rounded-lg border border-slate-200 p-3 text-sm">
                            <div class="font-medium text-slate-800">
                                {{ $event->fromStatus?->label ?? 'Created' }}
                                →
                                {{ $event->toStatus?->label ?? '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $event->actor?->name ?? 'System' }}
                                ·
                                {{ $event->created_at?->timezone('Asia/Manila')->format('M j, Y g:ia') }}
                            </div>
                            @if ($event->note)
                                <p class="mt-2 text-slate-600">{{ $event->note }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No workflow events yet.</p>
                    @endforelse
                </div>
            </x-mary-card>
        </div>

        <div class="space-y-4">
            <x-mary-card title="Details">
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-slate-500">Created by</dt>
                        <dd>{{ $submission->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Created</dt>
                        <dd>{{ $submission->created_at?->timezone('Asia/Manila')->format('M j, Y g:ia') }}</dd>
                    </div>
                    @if ($submission->resident)
                        <div>
                            <dt class="text-slate-500">Resident</dt>
                            <dd>
                                <a class="link" href="{{ route('residents.show', $submission->resident_id) }}">{{ $submission->resident->full_name }}</a>
                                <div class="text-xs text-slate-500">{{ $submission->resident->resident_id }}</div>
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-mary-card>

            @if ($canProcess)
                <x-mary-card title="Move status">
                    @if ($targets->isEmpty())
                        <p class="text-sm text-slate-500">No further transitions are allowed from this status.</p>
                    @else
                        <div class="space-y-3">
                            <select class="select select-bordered w-full" wire:model="nextStatusId">
                                <option value="">Choose status</option>
                                @foreach ($targets as $target)
                                    <option value="{{ $target->id }}">{{ $target->label }}</option>
                                @endforeach
                            </select>
                            <x-mary-textarea label="Note" wire:model="note" rows="2" />
                            <x-mary-button class="btn-primary w-full" wire:click="transition">Update status</x-mary-button>
                            @error('status')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('nextStatusId')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </x-mary-card>

                <x-mary-card title="Tags">
                    @if ($submission->form->tags->isEmpty())
                        <p class="text-sm text-slate-500">This form has no tags yet.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($submission->form->tags as $tag)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" class="checkbox checkbox-sm" wire:model="selectedTagIds" value="{{ $tag->id }}" />
                                    <span class="badge {{ $tag->badgeClass() }}">{{ $tag->label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-mary-button class="btn-outline btn-sm mt-3" wire:click="saveTags">Save tags</x-mary-button>
                    @endif
                </x-mary-card>
            @else
                <x-mary-card title="Tags">
                    <div class="flex flex-wrap gap-1">
                        @forelse ($submission->tags as $tag)
                            <span class="badge {{ $tag->badgeClass() }}">{{ $tag->label }}</span>
                        @empty
                            <p class="text-sm text-slate-500">No tags</p>
                        @endforelse
                    </div>
                </x-mary-card>
            @endif
        </div>
    </div>
</div>
