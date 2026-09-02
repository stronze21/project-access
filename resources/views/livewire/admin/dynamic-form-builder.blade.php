<div>
    <div class="mb-6">
        <a href="{{ route('forms.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Forms</a>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $title ?: 'Form builder' }}</h1>
                <p class="mt-1 text-sm text-gray-600">Add questions, a status pipeline, and tags. Saving updates the form definition without creating a new database table.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" wire:click="save" class="btn btn-outline btn-sm inline-flex h-9 min-h-9 items-center">Save</button>
                <button type="button" wire:click="publish" class="btn btn-primary btn-sm inline-flex h-9 min-h-9 items-center">Save and publish</button>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-mary-input label="Title" wire:model.live.debounce.400ms="title" />
            <div>
                <div class="label pt-0">
                    <span class="label-text font-semibold">Slug</span>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm text-slate-600">{{ $slug ?: 'generated-on-create' }}</div>
                <p class="mt-1 text-xs text-slate-400">Auto-generated and unique. It cannot be changed after the form is created.</p>
            </div>
            <div class="md:col-span-2">
                <x-mary-textarea label="Description" wire:model="description" rows="2" />
            </div>
            <x-mary-checkbox label="Active" wire:model="isActive" />
            <x-mary-checkbox label="Require a linked resident" wire:model="linkToResident" hint="Staff must pick a resident when filling this form" />
        </div>
    </x-mary-card>

    @php
        $builderTabs = [
            'fields' => [
                'label' => 'Questions',
                'count' => count($fields),
                'pill' => 'Build',
                'hint' => 'Add the questions staff will answer. Each key is generated from the label and locks after save.',
            ],
            'workflow' => [
                'label' => 'Status workflow',
                'count' => count($statuses),
                'pill' => 'Pipeline',
                'hint' => 'Define statuses and which moves are allowed. After submit, submissions follow this path.',
            ],
            'tags' => [
                'label' => 'Tags',
                'count' => count($tags),
                'pill' => 'Labels',
                'hint' => 'Optional tags staff can apply while processing a submission in the inbox.',
            ],
        ];
    @endphp

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex overflow-x-auto bg-slate-50/90">
            @foreach ($builderTabs as $tab => $meta)
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $tab }}')"
                    class="relative flex items-center gap-2 whitespace-nowrap border-t-4 px-5 py-3 text-sm font-semibold transition
                        {{ $activeTab === $tab
                            ? 'border-[var(--brand-accent)] bg-white text-[var(--brand-secondary-strong)]'
                            : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                >
                    {{ $meta['label'] }}
                    <span class="rounded-full bg-slate-200/90 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $meta['count'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-[#f8e6d0] px-5 py-2.5 text-sm text-slate-700">
            <span class="rounded-full bg-white/85 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $builderTabs[$activeTab]['pill'] }}</span>
            <span>{{ $builderTabs[$activeTab]['hint'] }}</span>
        </div>

        <div class="p-5">
            @if ($activeTab === 'fields')
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @foreach ($fieldTypes as $type => $label)
                        <button type="button" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center" wire:click="addField('{{ $type }}')">+ {{ $label }}</button>
                    @endforeach
                </div>

                <div class="space-y-4">
                    @forelse ($fields as $index => $field)
                        <div class="rounded-xl border border-slate-200 p-4" wire:key="field-{{ $field['temp_id'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-medium text-slate-500">Question {{ $index + 1 }}</div>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="btn btn-ghost btn-xs inline-flex h-7 min-h-7 items-center" wire:click="moveField({{ $index }}, -1)">Up</button>
                                    <button type="button" class="btn btn-ghost btn-xs inline-flex h-7 min-h-7 items-center" wire:click="moveField({{ $index }}, 1)">Down</button>
                                    <button type="button" class="btn btn-ghost btn-xs inline-flex h-7 min-h-7 items-center text-error" wire:click="removeField({{ $index }})">Remove</button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-mary-input label="Label" wire:model.live.debounce.300ms="fields.{{ $index }}.label" />
                                <div>
                                    <div class="label pt-0">
                                        <span class="label-text font-semibold">Key</span>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm text-slate-600">
                                        {{ $field['key'] !== '' ? $field['key'] : 'generated-from-label' }}
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">
                                        @if (! empty($field['id']))
                                            Locked after save. Used in answers, filters, and CSV columns.
                                        @else
                                            Generated from the label. It locks when you save the form.
                                        @endif
                                    </p>
                                </div>
                                <x-mary-select
                                    label="Type"
                                    wire:model="fields.{{ $index }}.type"
                                    :options="collect($fieldTypes)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all()"
                                />
                                <x-mary-input label="Help text" wire:model="fields.{{ $index }}.help_text" />
                            </div>
                            <div class="mt-3 flex flex-wrap gap-4">
                                <x-mary-checkbox label="Required" wire:model="fields.{{ $index }}.is_required" />
                                <x-mary-checkbox label="Filterable in inbox / CSV" wire:model="fields.{{ $index }}.is_filterable" />
                                <x-mary-checkbox label="Active" wire:model="fields.{{ $index }}.is_active" />
                            </div>
                            @if (in_array($field['type'], ['dropdown', 'radio', 'checkboxes'], true))
                                <div class="mt-4">
                                    <div class="mb-2 text-sm font-medium text-slate-700">Options</div>
                                    @foreach ($field['options'] ?? [] as $optionIndex => $option)
                                        <div class="mb-2 flex items-center gap-2" wire:key="field-{{ $field['temp_id'] }}-option-{{ $optionIndex }}">
                                            <input type="text" class="input input-bordered h-10 min-h-10 w-full" wire:model="fields.{{ $index }}.options.{{ $optionIndex }}" placeholder="Option label" />
                                            <button type="button" class="btn btn-ghost btn-sm inline-flex h-10 min-h-10 w-10 items-center justify-center" wire:click="removeOption({{ $index }}, {{ $optionIndex }})">×</button>
                                        </div>
                                    @endforeach
                                    <button type="button" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center" wire:click="addOption({{ $index }})">Add option</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center">
                            <p class="font-semibold text-slate-800">Nothing in this stage</p>
                            <p class="mt-1 text-sm text-slate-500">Add a question to get started. Short text, choices, dates, files, and resident lookup are available.</p>
                        </div>
                    @endforelse
                </div>
            @endif

            @if ($activeTab === 'workflow')
                <div class="mb-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-800">Statuses</h2>
                        <button type="button" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center" wire:click="addStatus">Add status</button>
                    </div>
                    <div class="space-y-3">
                        @foreach ($statuses as $index => $status)
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-6" wire:key="status-{{ $status['temp_id'] }}">
                                <x-mary-input label="Label" wire:model.live.debounce.300ms="statuses.{{ $index }}.label" />
                                <div>
                                    <div class="label pt-0">
                                        <span class="label-text font-semibold">Key</span>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm text-slate-600">
                                        {{ $status['key'] !== '' ? $status['key'] : 'generated-from-label' }}
                                    </div>
                                </div>
                                <x-mary-select
                                    label="Color"
                                    wire:model="statuses.{{ $index }}.color"
                                    :options="collect($colorOptions)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all()"
                                />
                                <x-mary-checkbox class="mt-6" label="Initial" wire:model="statuses.{{ $index }}.is_initial" />
                                <x-mary-checkbox class="mt-6" label="Terminal" wire:model="statuses.{{ $index }}.is_terminal" />
                                <div class="flex items-end">
                                    <button type="button" class="btn btn-ghost btn-sm inline-flex h-10 min-h-10 items-center text-error" wire:click="removeStatus({{ $index }})">Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="mb-2 font-semibold text-slate-800">Allowed transitions</h2>
                    <p class="mb-4 text-sm text-slate-500">Check which statuses a submission can move to. Leave a row empty only if that status is terminal.</p>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    @foreach ($statuses as $to)
                                        <th>{{ $to['label'] ?: 'Untitled' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statuses as $from)
                                    <tr wire:key="from-{{ $from['temp_id'] }}">
                                        <td class="font-medium">{{ $from['label'] ?: 'Untitled' }}</td>
                                        @foreach ($statuses as $to)
                                            <td>
                                                @if ($from['temp_id'] !== $to['temp_id'])
                                                    <input
                                                        type="checkbox"
                                                        value="{{ $to['temp_id'] }}"
                                                        wire:model="transitionMap.{{ $from['temp_id'] }}"
                                                        class="checkbox checkbox-sm"
                                                    />
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($activeTab === 'tags')
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-800">Tags</h2>
                    <button type="button" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center" wire:click="addTag">Add tag</button>
                </div>
                <div class="space-y-3">
                    @forelse ($tags as $index => $tag)
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3" wire:key="tag-{{ $index }}">
                            <x-mary-input label="Label" wire:model="tags.{{ $index }}.label" />
                            <x-mary-select
                                label="Color"
                                wire:model="tags.{{ $index }}.color"
                                :options="collect($colorOptions)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values()->all()"
                            />
                            <div class="flex items-end">
                                <button type="button" class="btn btn-ghost btn-sm inline-flex h-10 min-h-10 items-center text-error" wire:click="removeTag({{ $index }})">Remove</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center">
                            <p class="font-semibold text-slate-800">Nothing in this stage</p>
                            <p class="mt-1 text-sm text-slate-500">Tags are optional labels staff can apply while processing a submission.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
