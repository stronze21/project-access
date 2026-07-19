<div>
    @php
        $moduleSettings = app(\App\Services\ModuleSettings::class);
        $bosesmotoEnabled = $moduleSettings->enabled('bosesmoto');
        $bosesmotoComplaintsEnabled = $moduleSettings->enabled('complaints');
        $bosesmotoSentimentsEnabled = $moduleSettings->enabled('sentiments');
        $bosesmotoPollsEnabled = $moduleSettings->enabled('polls');
    @endphp

    <div class="mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Citizen Services</h1>
                <p class="mt-1 text-sm text-gray-600">Manage portal links, emergency alerts, SOS cases, and command center details. BosesMoto handles complaints, public feedback, polls, and reporting.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6 xl:grid-cols-6">
        <x-mary-stat title="Open Cases" value="{{ number_format($this->overviewStats['open_complaints']) }}" icon="o-clipboard-document-list" class="tagged-color text-primary" />
        <x-mary-stat title="Alerts" value="{{ number_format($this->overviewStats['active_alerts']) }}" icon="o-bell-alert" class="tagged-color text-error" />
        <x-mary-stat title="Open SOS" value="{{ number_format($this->overviewStats['open_sos']) }}" icon="o-shield-exclamation" class="tagged-color text-secondary" />
        <x-mary-stat title="Portal Links" value="{{ number_format($this->overviewStats['portal_links']) }}" icon="o-globe-alt" class="tagged-color text-success" />
        <x-mary-stat title="Polls" value="{{ number_format($this->overviewStats['polls']) }}" icon="o-chart-bar" class="tagged-color text-info" />
        <x-mary-stat title="Posts" value="{{ number_format($this->overviewStats['sentiment_posts']) }}" icon="o-chat-bubble-left-right" class="tagged-color text-warning" />
    </div>

    <x-mary-card class="mb-6" wire:key="citizen-services-tabs-card">
        <div class="flex flex-wrap gap-2" wire:key="citizen-services-tabs-list">
            @foreach ($tabs as $tab => $label)
                <x-mary-button
                    type="button"
                    wire:key="citizen-services-tab-{{ $tab }}"
                    wire:click="changeTab('{{ $tab }}')"
                    class="{{ $activeTab === $tab ? 'btn-primary' : 'btn-outline' }}"
                >
                    {{ $label }}
                </x-mary-button>
            @endforeach
        </div>
    </x-mary-card>

    <div wire:key="citizen-services-panel-{{ $activeTab }}">
        @switch($activeTab)
            @case('overview')
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <x-mary-card title="BosesMoto Services">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @if ($bosesmotoEnabled)
                                <a href="{{ route('bosesmoto.dashboard') }}" class="rounded-lg border border-slate-200 p-3 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    BosesMoto Dashboard
                                </a>
                            @endif
                            @if ($bosesmotoComplaintsEnabled && auth()->user()->hasAnyRole(['Admin', 'Super Admin', 'Mayor', 'Department Head', 'Action Officer', 'system-administrator', 'mayor', 'department-head', 'action-officer']))
                                <a href="{{ route('complaints.manage.index') }}" class="rounded-lg border border-slate-200 p-3 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    Complaint Queue
                                </a>
                            @endif
                            @if ($bosesmotoSentimentsEnabled)
                                <a href="{{ route('sentiments.index') }}" class="rounded-lg border border-slate-200 p-3 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    Sentiments
                                </a>
                            @endif
                            @if ($bosesmotoPollsEnabled)
                                <a href="{{ route('polls.index') }}" class="rounded-lg border border-slate-200 p-3 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    Polls
                                </a>
                            @endif
                        </div>
                    </x-mary-card>

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
                                    @if ($sos->department)
                                        <div class="mt-1 text-sm font-medium text-slate-700">{{ $sos->department->name }}</div>
                                    @endif
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
            @break

            @case('links')
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
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No portal links configured yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-mary-card>
            @break

            @case('service-types')
                <x-mary-card>
                    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Service Types</h2>
                            <p class="text-sm text-gray-500">Options used by resident service requests and public-service links.</p>
                        </div>
                        <x-mary-button wire:click="createServiceType" class="btn-primary">Add Service Type</x-mary-button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-base-200 text-slate-600"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Order</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($serviceTypes as $type)
                                    <tr class="border-b"><td class="px-4 py-3"><div class="font-medium">{{ $type->name }}</div>@if($type->description)<div class="text-xs text-gray-500">{{ $type->description }}</div>@endif</td><td class="px-4 py-3 font-mono text-xs">{{ $type->code }}</td><td class="px-4 py-3"><x-mary-badge value="{{ $type->is_active ? 'ACTIVE' : 'INACTIVE' }}" class="badge-sm {{ $type->is_active ? 'badge-success' : 'badge-ghost' }}" /></td><td class="px-4 py-3">{{ $type->sort_order }}</td><td class="px-4 py-3"><div class="flex justify-end gap-2"><x-mary-button wire:click="editServiceType({{ $type->id }})" icon="o-pencil" class="btn-ghost btn-sm" /><x-mary-button wire:click="deleteServiceType({{ $type->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" /></div></td></tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No service types configured.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-mary-card>
            @break

            @case('requests')
                <x-mary-card title="Service Tracking Management">
                    <div class="space-y-4">
                        @forelse ($serviceRequests as $request)
                            <div class="p-4 border rounded-lg">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $request->service_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $request->reference_number }} • {{ $request->resident?->full_name ?? 'External request' }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <x-mary-badge value="{{ strtoupper($request->status) }}" class="badge-sm badge-ghost" />
                                            <span>Step: {{ $request->current_step ?: 'Not set' }}</span>
                                            <span>Updated: {{ optional($request->status_updated_at)->format('M d, Y h:i A') ?: 'N/A' }}</span>
                                        </div>
                                        @if ($request->notes)
                                            <div class="mt-3 text-sm text-gray-700">{{ $request->notes }}</div>
                                        @endif
                                    </div>
                                    <div class="flex justify-end">
                                        <x-mary-button wire:click="editServiceRequest({{ $request->id }})" icon="o-pencil-square" class="btn-primary btn-sm">Edit Request</x-mary-button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-gray-500">No service requests found.</p>
                        @endforelse
                    </div>
                </x-mary-card>
            @break

            @case('grievances')
                <x-mary-card title="Feedback and Grievance Management">
                    <div class="space-y-4">
                        @forelse ($grievances as $grievance)
                            <div class="p-4 border rounded-lg">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $grievance->subject }}</div>
                                        <div class="text-sm text-gray-500">{{ $grievance->reference_number }} • {{ $grievance->category }} • {{ $grievance->resident?->full_name ?? 'Anonymous/guest' }}</div>
                                        <div class="mt-2 text-sm text-gray-700">{{ $grievance->description }}</div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <x-mary-badge value="{{ strtoupper($grievance->status) }}" class="badge-sm badge-ghost" />
                                            @if ($grievance->resolved_at)
                                                <span>Resolved: {{ $grievance->resolved_at->format('M d, Y h:i A') }}</span>
                                            @endif
                                        </div>
                                        @if ($grievance->admin_response)
                                            <div class="mt-3 text-sm text-gray-700">
                                                <span class="font-medium text-slate-800">Admin response:</span> {{ $grievance->admin_response }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex justify-end">
                                        <x-mary-button wire:click="editGrievance({{ $grievance->id }})" icon="o-pencil-square" class="btn-primary btn-sm">Edit Grievance</x-mary-button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-gray-500">No grievances found.</p>
                        @endforelse
                    </div>
                </x-mary-card>
            @break

            @case('alerts')
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
                                        <td class="px-4 py-3 text-right">
                                            <x-mary-button wire:click="editAlert({{ $alert->id }})" icon="o-pencil" class="btn-ghost btn-sm" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No emergency alerts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-mary-card>
            @break

            @case('sos')
                <div class="space-y-6">
                    <x-mary-card title="Active SOS Alert Map">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-600">
                                All open and acknowledged SOS alerts with coordinates are shown on this map.
                            </p>
                            <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full bg-red-600 ring-2 ring-red-200"></span>
                                    Open
                                </span>
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full bg-blue-600 ring-2 ring-blue-200"></span>
                                    Acknowledged
                                </span>
                            </div>
                        </div>

                        <div
                            wire:key="sos-alert-map-{{ md5(json_encode($sosMapAlerts)) }}"
                            x-data
                            x-init="$nextTick(() => window.initializeSosAlertMap($refs.map, {{ Illuminate\Support\Js::from($sosMapAlerts) }}))"
                        >
                            <div x-ref="map" wire:ignore class="sos-alert-map h-[32rem] w-full rounded-xl border border-slate-200 bg-slate-100"></div>
                            @if (empty($sosMapAlerts))
                                <p class="mt-3 text-center text-sm text-gray-500">No open or acknowledged SOS alerts currently have coordinates.</p>
                            @endif
                        </div>
                    </x-mary-card>

                    <x-mary-card title="SOS Response Management">
                    <div class="space-y-4">
                        @forelse ($sosAlerts as $sos)
                            <div class="p-4 border rounded-lg">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $sos->resident?->full_name ?? 'Unknown resident' }}</div>
                                        <div class="text-sm text-gray-500">{{ $sos->reference_number }} • {{ $sos->contact_number ?: 'No contact number' }}</div>
                                        @if ($sos->department)
                                            <div class="mt-1 text-sm font-medium text-slate-700">Inform: {{ $sos->department->name }}</div>
                                        @endif
                                        @if ($sos->message)
                                            <div class="mt-2 text-sm text-gray-700">{{ $sos->message }}</div>
                                        @endif
                                        @if ($sos->location_label)
                                            <div class="mt-1 text-xs text-gray-500">{{ $sos->location_label }}</div>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <x-mary-badge value="{{ strtoupper($sos->status) }}" class="badge-sm badge-ghost" />
                                            @if ($sos->acknowledged_at)
                                                <span>Acknowledged: {{ $sos->acknowledged_at->format('M d, Y h:i A') }}</span>
                                            @endif
                                            @if ($sos->resolved_at)
                                                <span>Resolved: {{ $sos->resolved_at->format('M d, Y h:i A') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <x-mary-button wire:click="editSos({{ $sos->id }})" icon="o-pencil-square" class="btn-primary btn-sm">Edit SOS</x-mary-button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-gray-500">No SOS alerts found.</p>
                        @endforelse
                    </div>
                    </x-mary-card>
                </div>
            @break

            @case('command-center')
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
            @break
        @endswitch
    </div>

    @if ($showActionModal && $actionModalType === 'link')
        <x-mary-modal wire:model="showActionModal" title="{{ $editingLinkId ? 'Edit Portal Link' : 'New Portal Link' }}" box-class="max-w-2xl">
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
                    <x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">Save Link</x-mary-button>
                </div>
            </form>
        </x-mary-modal>
    @endif

    @if ($showActionModal && $actionModalType === 'service-type')
        <x-mary-modal wire:model="showActionModal" title="{{ $editingServiceTypeId ? 'Edit Service Type' : 'New Service Type' }}" box-class="max-w-2xl">
            <form wire:submit.prevent="saveServiceType">
                <div class="space-y-4">
                    <x-mary-input label="Name" wire:model="serviceTypeName" />
                    <x-mary-input label="Code" wire:model="serviceTypeCode" hint="Stable value used by the app and API, e.g. business-permit" />
                    <x-mary-textarea label="Description" wire:model="serviceTypeDescription" rows="3" />
                    <x-mary-input label="Sort Order" wire:model="serviceTypeSortOrder" type="number" />
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="serviceTypeIsActive" class="checkbox checkbox-primary"><span class="label-text">Active</span></label>
                </div>
                <div class="flex justify-end mt-6 gap-2"><x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button><x-mary-button type="submit" class="btn-primary">Save Service Type</x-mary-button></div>
            </form>
        </x-mary-modal>
    @endif

    @if ($showActionModal && $actionModalType === 'alert')
        <x-mary-modal wire:model="showActionModal" title="{{ $editingAlertId ? 'Edit Emergency Alert' : 'New Emergency Alert' }}" box-class="max-w-3xl">
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
                    <x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">Save Alert</x-mary-button>
                </div>
            </form>
        </x-mary-modal>
    @endif

    @if ($showActionModal && $actionModalType === 'request')
        <x-mary-modal wire:model="showActionModal" title="Edit Service Request" box-class="max-w-2xl">
            <form wire:submit.prevent="saveServiceRequest">
                <div class="space-y-4">
                    <x-mary-select label="Status" wire:model="requestStatus" :options="$serviceStatusOptions" />
                    <x-mary-input label="Current Step" wire:model="requestStep" />
                    <x-mary-input label="Expected Completion" wire:model="requestExpectedCompletionAt" type="datetime-local" />
                    <x-mary-textarea label="Request Notes" rows="4" wire:model="requestNote" />
                </div>
                <div class="flex justify-end mt-6 gap-2">
                    <x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">Save Request Update</x-mary-button>
                </div>
            </form>
        </x-mary-modal>
    @endif

    @if ($showActionModal && $actionModalType === 'grievance')
        <x-mary-modal wire:model="showActionModal" title="Edit Grievance" box-class="max-w-2xl">
            <form wire:submit.prevent="saveGrievance">
                <div class="space-y-4">
                    <x-mary-select label="Status" wire:model="grievanceStatus" :options="$grievanceStatusOptions" />
                    <x-mary-textarea label="Admin Response" rows="4" wire:model="grievanceResponse" />
                </div>
                <div class="flex justify-end mt-6 gap-2">
                    <x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">Save Grievance Update</x-mary-button>
                </div>
            </form>
        </x-mary-modal>
    @endif

    @if ($showActionModal && $actionModalType === 'sos')
        <x-mary-modal wire:model="showActionModal" title="Edit SOS Alert" box-class="max-w-xl">
            <form wire:submit.prevent="saveSos">
                <div class="space-y-4">
                    <x-mary-select label="Status" wire:model="sosStatus" :options="$sosStatusOptions" />
                </div>
                <div class="flex justify-end mt-6 gap-2">
                    <x-mary-button type="button" wire:click="$set('showActionModal', false); $set('actionModalType', null)" class="btn-outline">Cancel</x-mary-button>
                    <x-mary-button type="submit" class="btn-primary">Save SOS Update</x-mary-button>
                </div>
            </form>
        </x-mary-modal>
    @endif

    @once
        @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
            .sos-alert-map-marker {
                background: transparent;
                border: 0;
            }

            .sos-alert-map-pin {
                align-items: center;
                border: 2px solid #fff;
                border-radius: 50% 50% 50% 0;
                box-shadow: 0 2px 8px rgb(15 23 42 / 35%);
                display: flex;
                height: 28px;
                justify-content: center;
                transform: rotate(-45deg);
                width: 28px;
            }

            .sos-alert-map-pin::after {
                background: #fff;
                border-radius: 9999px;
                content: '';
                height: 8px;
                width: 8px;
            }

            .sos-alert-map-pin-open {
                background: #dc2626;
            }

            .sos-alert-map-pin-acknowledged {
                background: #2563eb;
            }
        </style>
        <script>
            window.initializeSosAlertMap = (element, alerts) => {
                if (!element || element.dataset.mapInitialized === 'true') {
                    return;
                }

                if (typeof window.L === 'undefined') {
                    element.innerHTML = '<div class="flex h-full items-center justify-center p-6 text-center text-sm text-gray-500">The map service could not load. Refresh the page to try again.</div>';
                    return;
                }

                element.dataset.mapInitialized = 'true';

                const defaultCenter = [16.1555, 119.9814];
                const map = L.map(element).setView(defaultCenter, 13);
                const bounds = [];

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                const escapeHtml = (value) => String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');

                alerts.forEach((alert) => {
                    const latitude = Number(alert.latitude);
                    const longitude = Number(alert.longitude);

                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)
                        || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                        return;
                    }

                    const status = alert.status === 'acknowledged' ? 'acknowledged' : 'open';
                    const markerIcon = L.divIcon({
                        className: 'sos-alert-map-marker',
                        html: `<span class="sos-alert-map-pin sos-alert-map-pin-${status}"></span>`,
                        iconAnchor: [14, 28],
                        iconSize: [28, 28],
                        popupAnchor: [0, -27],
                    });

                    const details = [
                        alert.location ? `<div>${escapeHtml(alert.location)}</div>` : '',
                        alert.contact ? `<div>Contact: ${escapeHtml(alert.contact)}</div>` : '',
                        alert.department ? `<div>Inform: ${escapeHtml(alert.department)}</div>` : '',
                        alert.reported_at ? `<div>Reported: ${escapeHtml(alert.reported_at)}</div>` : '',
                    ].filter(Boolean).join('');

                    L.marker([latitude, longitude], {
                        icon: markerIcon,
                        title: `${alert.reference} - ${alert.resident}`,
                    })
                        .addTo(map)
                        .bindPopup(`
                            <div class="min-w-52 text-sm">
                                <div class="font-semibold">${escapeHtml(alert.resident)}</div>
                                <div class="mb-2 text-xs uppercase">${escapeHtml(alert.status)} &middot; ${escapeHtml(alert.reference)}</div>
                                ${details}
                            </div>
                        `);

                    bounds.push([latitude, longitude]);
                });

                if (bounds.length === 1) {
                    map.setView(bounds[0], 16);
                } else if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 16 });
                }

                setTimeout(() => map.invalidateSize(), 0);
            };
        </script>
        @endpush
    @endonce
</div>
