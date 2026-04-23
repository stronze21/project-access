<div>
    <div class="mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Citizen Services</h1>
                <p class="mt-1 text-sm text-gray-600">Manage portal links, service tracking, grievances, emergency alerts, SOS cases, and command center details.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6 xl:grid-cols-6">
        <x-mary-stat title="Requests" value="{{ number_format($this->overviewStats['service_requests']) }}" icon="o-clipboard-document-list" class="tagged-color text-primary" />
        <x-mary-stat title="Active" value="{{ number_format($this->overviewStats['active_requests']) }}" icon="o-clock" class="tagged-color text-info" />
        <x-mary-stat title="Grievances" value="{{ number_format($this->overviewStats['open_grievances']) }}" icon="o-chat-bubble-left-right" class="tagged-color text-warning" />
        <x-mary-stat title="Alerts" value="{{ number_format($this->overviewStats['active_alerts']) }}" icon="o-bell-alert" class="tagged-color text-error" />
        <x-mary-stat title="Open SOS" value="{{ number_format($this->overviewStats['open_sos']) }}" icon="o-shield-exclamation" class="tagged-color text-secondary" />
        <x-mary-stat title="Portal Links" value="{{ number_format($this->overviewStats['portal_links']) }}" icon="o-globe-alt" class="tagged-color text-success" />
    </div>

    <x-mary-card class="mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach ([
                'overview' => 'Overview',
                'links' => 'Portal Links',
                'requests' => 'Service Tracking',
                'grievances' => 'Grievances',
                'alerts' => 'Emergency Alerts',
                'sos' => 'SOS Alerts',
                'command-center' => 'Command Center',
            ] as $tab => $label)
                <x-mary-button wire:click="changeTab('{{ $tab }}')" class="{{ $activeTab === $tab ? 'btn-primary' : 'btn-outline' }}">
                    {{ $label }}
                </x-mary-button>
            @endforeach
        </div>
    </x-mary-card>

    @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-mary-card title="Recent Emergency Alerts">
                <div class="space-y-3">
                    @forelse ($emergencyAlerts->take(5) as $alert)
                        <div class="p-3 border rounded-lg">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-slate-800">{{ $alert->title }}</div>
                                <x-mary-badge value="{{ strtoupper($alert->status) }}" class="badge-sm badge-ghost" />
                            </div>
                            <div class="mt-1 text-sm text-gray-600">{{ $alert->message }}</div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-500">No emergency alerts found.</p>
                    @endforelse
                </div>
            </x-mary-card>

            <x-mary-card title="Recent SOS Alerts">
                <div class="space-y-3">
                    @forelse ($sosAlerts->take(5) as $sos)
                        <div class="p-3 border rounded-lg">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-medium text-slate-800">{{ $sos->resident?->full_name ?? 'Unknown resident' }}</div>
                                <x-mary-badge value="{{ strtoupper($sos->status) }}" class="badge-sm badge-ghost" />
                            </div>
                            <div class="mt-1 text-xs text-gray-500">{{ $sos->reference_number }} • {{ $sos->created_at->diffForHumans() }}</div>
                            @if ($sos->location_label)
                                <div class="mt-1 text-sm text-gray-600">{{ $sos->location_label }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-500">No SOS alerts found.</p>
                    @endforelse
                </div>
            </x-mary-card>
        </div>
    @endif

    @if ($activeTab === 'links')
        <x-mary-card>
            <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Public Service Portal Links</h2>
                <x-mary-button wire:click="createLink" class="btn-primary">Add Portal Link</x-mary-button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-base-200 text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">URL</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($serviceLinks as $link)
                            <tr class="border-b">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $link->title }}</div>
                                    @if ($link->description)
                                        <div class="text-xs text-gray-500">{{ $link->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $link->service_type }}</td>
                                <td class="px-4 py-3"><a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="brand-link">{{ $link->url }}</a></td>
                                <td class="px-4 py-3"><x-mary-badge value="{{ $link->is_active ? 'ACTIVE' : 'INACTIVE' }}" class="badge-sm {{ $link->is_active ? 'badge-success' : 'badge-ghost' }}" /></td>
                                <td class="px-4 py-3">{{ $link->sort_order }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-mary-button wire:click="editLink({{ $link->id }})" icon="o-pencil" class="btn-ghost btn-sm" />
                                        <x-mary-button wire:click="deleteLink({{ $link->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No portal links configured yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    @if ($activeTab === 'requests')
        <x-mary-card title="Service Tracking Management">
            <div class="space-y-4">
                @forelse ($serviceRequests as $request)
                    <div class="p-4 border rounded-lg">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">{{ $request->service_name }}</div>
                                <div class="text-sm text-gray-500">{{ $request->reference_number }} • {{ $request->resident?->full_name ?? 'External request' }}</div>
                            </div>
                            <div class="w-full lg:w-56">
                                <x-mary-select wire:model="requestStatuses.{{ $request->id }}" :options="$serviceStatusOptions" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4 lg:grid-cols-2">
                            <x-mary-input label="Current Step" wire:model="requestSteps.{{ $request->id }}" />
                            <x-mary-input label="Last Updated" value="{{ optional($request->status_updated_at)->format('M d, Y h:i A') }}" readonly />
                        </div>
                        <div class="mt-4">
                            <x-mary-textarea label="Internal Notes" rows="3" wire:model="requestNotes.{{ $request->id }}" />
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-mary-button wire:click="saveServiceRequest({{ $request->id }})" class="btn-primary btn-sm">Save Request Update</x-mary-button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-gray-500">No service requests found.</p>
                @endforelse
            </div>
        </x-mary-card>
    @endif

    @if ($activeTab === 'grievances')
        <x-mary-card title="Feedback and Grievance Management">
            <div class="space-y-4">
                @forelse ($grievances as $grievance)
                    <div class="p-4 border rounded-lg">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">{{ $grievance->subject }}</div>
                                <div class="text-sm text-gray-500">{{ $grievance->reference_number }} • {{ $grievance->category }} • {{ $grievance->resident?->full_name ?? 'Anonymous/guest' }}</div>
                                <div class="mt-2 text-sm text-gray-700">{{ $grievance->description }}</div>
                            </div>
                            <div class="w-full lg:w-56">
                                <x-mary-select wire:model="grievanceStatuses.{{ $grievance->id }}" :options="$grievanceStatusOptions" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-mary-textarea label="Admin Response" rows="3" wire:model="grievanceResponses.{{ $grievance->id }}" />
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-mary-button wire:click="saveGrievance({{ $grievance->id }})" class="btn-primary btn-sm">Save Grievance Update</x-mary-button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-gray-500">No grievances found.</p>
                @endforelse
            </div>
        </x-mary-card>
    @endif

    @if ($activeTab === 'alerts')
        <x-mary-card>
            <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Emergency Alerts</h2>
                <x-mary-button wire:click="createAlert" class="btn-primary">New Emergency Alert</x-mary-button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-base-200 text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Alert</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Severity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Window</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($emergencyAlerts as $alert)
                            <tr class="border-b">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $alert->title }}</div>
                                    <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($alert->message, 90) }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $alert->alert_type }}</td>
                                <td class="px-4 py-3">{{ $alert->severity }}</td>
                                <td class="px-4 py-3">{{ $alert->status }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $alert->starts_at?->format('M d, Y h:i A') ?? 'Now' }}<br>{{ $alert->ends_at?->format('M d, Y h:i A') ?? 'No end' }}</td>
                                <td class="px-4 py-3 text-right"><x-mary-button wire:click="editAlert({{ $alert->id }})" icon="o-pencil" class="btn-ghost btn-sm" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No emergency alerts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-mary-card>
    @endif

    @if ($activeTab === 'sos')
        <x-mary-card title="SOS Response Management">
            <div class="space-y-4">
                @forelse ($sosAlerts as $sos)
                    <div class="p-4 border rounded-lg">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="font-semibold text-slate-800">{{ $sos->resident?->full_name ?? 'Unknown resident' }}</div>
                                <div class="text-sm text-gray-500">{{ $sos->reference_number }} • {{ $sos->contact_number ?: 'No contact number' }}</div>
                                @if ($sos->message)
                                    <div class="mt-2 text-sm text-gray-700">{{ $sos->message }}</div>
                                @endif
                                @if ($sos->location_label)
                                    <div class="mt-1 text-xs text-gray-500">{{ $sos->location_label }}</div>
                                @endif
                            </div>
                            <div class="w-full lg:w-56">
                                <x-mary-select wire:model="sosStatuses.{{ $sos->id }}" :options="$sosStatusOptions" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-mary-button wire:click="saveSos({{ $sos->id }})" class="btn-primary btn-sm">Save SOS Update</x-mary-button>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-gray-500">No SOS alerts found.</p>
                @endforelse
            </div>
        </x-mary-card>
    @endif

    @if ($activeTab === 'command-center')
        <x-mary-card title="Command Center Settings">
            <form wire:submit.prevent="saveCommandCenterSettings">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-mary-input label="Command Center Name" wire:model="commandCenterName" />
                    <x-mary-input label="Primary Hotline" wire:model="commandCenterHotline" />
                    <x-mary-input label="Alternate Hotline" wire:model="commandCenterAlternateHotline" />
                    <x-mary-input label="Command Center Email" wire:model="commandCenterEmail" type="email" />
                </div>
                <div class="flex justify-end mt-6">
                    <x-mary-button type="submit" class="btn-primary">Save Command Center Settings</x-mary-button>
                </div>
            </form>
        </x-mary-card>
    @endif

    <x-mary-modal wire:model="showLinkModal" title="{{ $editingLinkId ? 'Edit Portal Link' : 'New Portal Link' }}" box-class="max-w-2xl">
        <form wire:submit.prevent="saveLink">
            <div class="space-y-4">
                <x-mary-input label="Title" wire:model="linkTitle" />
                <x-mary-select label="Service Type" wire:model="linkServiceType" :options="$linkServiceTypes" />
                <x-mary-textarea label="Description" wire:model="linkDescription" rows="3" />
                <x-mary-input label="URL" wire:model="linkUrl" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-mary-input label="Icon Name" wire:model="linkIcon" hint="Optional icon key for future UI usage" />
                    <x-mary-input label="Sort Order" wire:model="linkSortOrder" type="number" />
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="linkIsActive" class="checkbox checkbox-primary"><span class="label-text">Active</span></label>
            </div>
            <div class="flex justify-end mt-6 gap-2">
                <x-mary-button type="button" wire:click="$set('showLinkModal', false)" class="btn-outline">Cancel</x-mary-button>
                <x-mary-button type="submit" class="btn-primary">Save Link</x-mary-button>
            </div>
        </form>
    </x-mary-modal>

    <x-mary-modal wire:model="showAlertModal" title="{{ $editingAlertId ? 'Edit Emergency Alert' : 'New Emergency Alert' }}" box-class="max-w-3xl">
        <form wire:submit.prevent="saveAlert">
            <div class="space-y-4">
                <x-mary-input label="Title" wire:model="alertTitle" />
                <x-mary-textarea label="Message" wire:model="alertMessage" rows="4" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-mary-select label="Severity" wire:model="alertSeverity" :options="$alertSeverityOptions" />
                    <x-mary-select label="Status" wire:model="alertStatus" :options="$alertStatusOptions" />
                    <x-mary-select label="Type" wire:model="alertType" :options="$alertTypeOptions" />
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-mary-input label="Starts At" wire:model="alertStartsAt" type="datetime-local" />
                    <x-mary-input label="Ends At" wire:model="alertEndsAt" type="datetime-local" />
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="alertSendPush" class="checkbox checkbox-primary"><span class="label-text">Send push notification to residents</span></label>
            </div>
            <div class="flex justify-end mt-6 gap-2">
                <x-mary-button type="button" wire:click="$set('showAlertModal', false)" class="btn-outline">Cancel</x-mary-button>
                <x-mary-button type="submit" class="btn-primary">Save Alert</x-mary-button>
            </div>
        </form>
    </x-mary-modal>
</div>
