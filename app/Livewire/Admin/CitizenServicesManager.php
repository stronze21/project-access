<?php

namespace App\Livewire\Admin;

use App\Models\CitizenServiceRequest;
use App\Models\EmergencyAlert;
use App\Models\GrievanceReport;
use App\Models\PublicServiceLink;
use App\Models\SosAlert;
use App\Models\SystemSetting;
use App\Services\PushNotificationService;
use Livewire\Component;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

class CitizenServicesManager extends Component
{
    use Toast;

    #[Url]
    public string $activeTab = 'overview';

    public bool $showLinkModal = false;
    public bool $showAlertModal = false;

    public ?int $editingLinkId = null;
    public string $linkTitle = '';
    public string $linkServiceType = 'business-permit';
    public string $linkDescription = '';
    public string $linkUrl = '';
    public string $linkIcon = '';
    public bool $linkIsActive = true;
    public int $linkSortOrder = 0;

    public ?int $editingAlertId = null;
    public string $alertTitle = '';
    public string $alertMessage = '';
    public string $alertSeverity = 'medium';
    public string $alertStatus = 'active';
    public string $alertType = 'general';
    public bool $alertSendPush = false;
    public ?string $alertStartsAt = null;
    public ?string $alertEndsAt = null;

    public array $requestStatuses = [];
    public array $requestSteps = [];
    public array $requestNotes = [];
    public array $grievanceStatuses = [];
    public array $grievanceResponses = [];
    public array $sosStatuses = [];

    public string $commandCenterName = '';
    public string $commandCenterHotline = '';
    public string $commandCenterAlternateHotline = '';
    public string $commandCenterEmail = '';

    public array $linkServiceTypes = [
        ['id' => 'business-permit', 'name' => 'Business Permit'],
        ['id' => 'civil-registry', 'name' => 'Civil Registry'],
        ['id' => 'tax-payment', 'name' => 'Tax Payment'],
        ['id' => 'financial-aid', 'name' => 'Financial Aid'],
        ['id' => 'general', 'name' => 'General Service'],
    ];

    public array $alertSeverityOptions = [
        ['id' => 'low', 'name' => 'Low'],
        ['id' => 'medium', 'name' => 'Medium'],
        ['id' => 'high', 'name' => 'High'],
        ['id' => 'critical', 'name' => 'Critical'],
    ];

    public array $alertStatusOptions = [
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'scheduled', 'name' => 'Scheduled'],
        ['id' => 'resolved', 'name' => 'Resolved'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
    ];

    public array $alertTypeOptions = [
        ['id' => 'general', 'name' => 'General'],
        ['id' => 'weather', 'name' => 'Weather'],
        ['id' => 'utility', 'name' => 'Utility'],
        ['id' => 'health', 'name' => 'Health'],
        ['id' => 'security', 'name' => 'Security'],
        ['id' => 'drill', 'name' => 'Drill'],
    ];

    public array $serviceStatusOptions = [
        ['id' => 'submitted', 'name' => 'Submitted'],
        ['id' => 'processing', 'name' => 'Processing'],
        ['id' => 'for-release', 'name' => 'For Release'],
        ['id' => 'completed', 'name' => 'Completed'],
        ['id' => 'released', 'name' => 'Released'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
        ['id' => 'rejected', 'name' => 'Rejected'],
    ];

    public array $grievanceStatusOptions = [
        ['id' => 'submitted', 'name' => 'Submitted'],
        ['id' => 'under-review', 'name' => 'Under Review'],
        ['id' => 'in-progress', 'name' => 'In Progress'],
        ['id' => 'resolved', 'name' => 'Resolved'],
        ['id' => 'closed', 'name' => 'Closed'],
    ];

    public array $sosStatusOptions = [
        ['id' => 'open', 'name' => 'Open'],
        ['id' => 'acknowledged', 'name' => 'Acknowledged'],
        ['id' => 'resolved', 'name' => 'Resolved'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
    ];

    public function mount(): void
    {
        $this->loadCommandCenterSettings();
        $this->primeFormMaps();
    }

    protected function rules(): array
    {
        return [
            'linkTitle' => 'required|string|max:255',
            'linkServiceType' => 'required|string|max:100',
            'linkDescription' => 'nullable|string',
            'linkUrl' => 'required|url|max:255',
            'linkIcon' => 'nullable|string|max:100',
            'linkSortOrder' => 'required|integer|min:0',
            'linkIsActive' => 'boolean',
            'alertTitle' => 'required|string|max:255',
            'alertMessage' => 'required|string',
            'alertSeverity' => 'required|string|max:50',
            'alertStatus' => 'required|string|max:50',
            'alertType' => 'required|string|max:100',
            'alertSendPush' => 'boolean',
            'alertStartsAt' => 'nullable|date',
            'alertEndsAt' => 'nullable|date|after_or_equal:alertStartsAt',
            'commandCenterName' => 'required|string|max:255',
            'commandCenterHotline' => 'required|string|max:50',
            'commandCenterAlternateHotline' => 'nullable|string|max:50',
            'commandCenterEmail' => 'nullable|email|max:255',
        ];
    }

    public function changeTab(string $tab): void
    {
        $this->showLinkModal = false;
        $this->showAlertModal = false;
        $this->activeTab = $tab;
    }

    public function createLink(): void
    {
        $this->showAlertModal = false;
        $this->resetLinkForm();
        $this->showLinkModal = true;
    }

    public function editLink(int $id): void
    {
        $this->showAlertModal = false;
        $link = PublicServiceLink::findOrFail($id);

        $this->editingLinkId = $link->id;
        $this->linkTitle = $link->title;
        $this->linkServiceType = $link->service_type;
        $this->linkDescription = $link->description ?? '';
        $this->linkUrl = $link->url;
        $this->linkIcon = $link->icon ?? '';
        $this->linkIsActive = (bool) $link->is_active;
        $this->linkSortOrder = (int) $link->sort_order;
        $this->showLinkModal = true;
    }

    public function saveLink(): void
    {
        $this->validateOnly('linkTitle');
        $this->validateOnly('linkServiceType');
        $this->validateOnly('linkUrl');
        $this->validateOnly('linkSortOrder');

        $payload = [
            'title' => $this->linkTitle,
            'service_type' => $this->linkServiceType,
            'description' => $this->linkDescription ?: null,
            'url' => $this->linkUrl,
            'icon' => $this->linkIcon ?: null,
            'is_active' => $this->linkIsActive,
            'sort_order' => $this->linkSortOrder,
        ];

        if ($this->editingLinkId) {
            PublicServiceLink::findOrFail($this->editingLinkId)->update($payload);
            $this->success('Portal link updated successfully.');
        } else {
            PublicServiceLink::create($payload);
            $this->success('Portal link created successfully.');
        }

        $this->resetLinkForm();
        $this->showLinkModal = false;
    }

    public function deleteLink(int $id): void
    {
        PublicServiceLink::findOrFail($id)->delete();
        $this->success('Portal link deleted successfully.');
    }

    public function createAlert(): void
    {
        $this->showLinkModal = false;
        $this->resetAlertForm();
        $this->showAlertModal = true;
    }

    public function editAlert(int $id): void
    {
        $this->showLinkModal = false;
        $alert = EmergencyAlert::findOrFail($id);

        $this->editingAlertId = $alert->id;
        $this->alertTitle = $alert->title;
        $this->alertMessage = $alert->message;
        $this->alertSeverity = $alert->severity;
        $this->alertStatus = $alert->status;
        $this->alertType = $alert->alert_type;
        $this->alertSendPush = (bool) $alert->send_push_notification;
        $this->alertStartsAt = $alert->starts_at?->format('Y-m-d\TH:i');
        $this->alertEndsAt = $alert->ends_at?->format('Y-m-d\TH:i');
        $this->showAlertModal = true;
    }

    public function saveAlert(PushNotificationService $pushNotificationService): void
    {
        $this->validateOnly('alertTitle');
        $this->validateOnly('alertMessage');
        $this->validateOnly('alertSeverity');
        $this->validateOnly('alertStatus');
        $this->validateOnly('alertType');
        $this->validateOnly('alertStartsAt');
        $this->validateOnly('alertEndsAt');

        $payload = [
            'title' => $this->alertTitle,
            'message' => $this->alertMessage,
            'severity' => $this->alertSeverity,
            'status' => $this->alertStatus,
            'alert_type' => $this->alertType,
            'send_push_notification' => $this->alertSendPush,
            'starts_at' => $this->alertStartsAt ?: null,
            'ends_at' => $this->alertEndsAt ?: null,
        ];

        if ($this->editingAlertId) {
            EmergencyAlert::findOrFail($this->editingAlertId)->update($payload);
            $this->success('Emergency alert updated successfully.');
        } else {
            $alert = EmergencyAlert::create(array_merge($payload, [
                'created_by' => auth()->id(),
                'metadata' => ['channel' => 'web-admin'],
            ]));

            if ($alert->send_push_notification && $alert->status === 'active') {
                $pushNotificationService->broadcastResidentNotification(
                    $alert->title,
                    $alert->message,
                    'emergency',
                    [
                        'emergency_alert_id' => (string) $alert->id,
                        'severity' => (string) $alert->severity,
                        'alert_type' => (string) $alert->alert_type,
                    ]
                );
            }

            $this->success('Emergency alert created successfully.');
        }

        $this->resetAlertForm();
        $this->showAlertModal = false;
    }

    public function saveServiceRequest(int $id): void
    {
        $request = CitizenServiceRequest::findOrFail($id);
        $status = $this->requestStatuses[$id] ?? $request->status;

        $request->update([
            'status' => $status,
            'current_step' => $this->requestSteps[$id] ?? $request->current_step,
            'notes' => $this->requestNotes[$id] ?? $request->notes,
            'status_updated_at' => now(),
            'completed_at' => in_array($status, ['completed', 'released'], true) ? now() : null,
        ]);

        $this->success('Service request updated.');
    }

    public function saveGrievance(int $id): void
    {
        $grievance = GrievanceReport::findOrFail($id);
        $status = $this->grievanceStatuses[$id] ?? $grievance->status;

        $grievance->update([
            'status' => $status,
            'admin_response' => $this->grievanceResponses[$id] ?? $grievance->admin_response,
            'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
        ]);

        $this->success('Grievance updated.');
    }

    public function saveSos(int $id): void
    {
        $sos = SosAlert::findOrFail($id);
        $status = $this->sosStatuses[$id] ?? $sos->status;

        $sos->update([
            'status' => $status,
            'acknowledged_at' => in_array($status, ['acknowledged', 'resolved'], true) ? ($sos->acknowledged_at ?? now()) : null,
            'resolved_at' => $status === 'resolved' ? now() : null,
        ]);

        $this->success('SOS alert updated.');
    }

    public function saveCommandCenterSettings(): void
    {
        $this->validateOnly('commandCenterName');
        $this->validateOnly('commandCenterHotline');
        $this->validateOnly('commandCenterAlternateHotline');
        $this->validateOnly('commandCenterEmail');

        foreach ([
            'command_center_name' => $this->commandCenterName,
            'command_center_hotline' => $this->commandCenterHotline,
            'command_center_alternate_hotline' => $this->commandCenterAlternateHotline,
            'command_center_email' => $this->commandCenterEmail,
        ] as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'emergency', 'type' => 'string', 'is_public' => true]
            );
        }

        SystemSetting::clearCache();
        $this->success('Command center settings updated.');
    }

    private function loadCommandCenterSettings(): void
    {
        $this->commandCenterName = (string) SystemSetting::get('command_center_name', 'Alaminos City Command Center');
        $this->commandCenterHotline = (string) SystemSetting::get('command_center_hotline', '911');
        $this->commandCenterAlternateHotline = (string) SystemSetting::get('command_center_alternate_hotline', '');
        $this->commandCenterEmail = (string) SystemSetting::get('command_center_email', '');
    }

    private function primeFormMaps(): void
    {
        foreach (CitizenServiceRequest::latest('status_updated_at')->limit(20)->get() as $request) {
            $this->requestStatuses[$request->id] = $request->status;
            $this->requestSteps[$request->id] = $request->current_step ?? '';
            $this->requestNotes[$request->id] = $request->notes ?? '';
        }

        foreach (GrievanceReport::latest()->limit(20)->get() as $grievance) {
            $this->grievanceStatuses[$grievance->id] = $grievance->status;
            $this->grievanceResponses[$grievance->id] = $grievance->admin_response ?? '';
        }

        foreach (SosAlert::latest()->limit(20)->get() as $sos) {
            $this->sosStatuses[$sos->id] = $sos->status;
        }
    }

    private function resetLinkForm(): void
    {
        $this->editingLinkId = null;
        $this->linkTitle = '';
        $this->linkServiceType = 'business-permit';
        $this->linkDescription = '';
        $this->linkUrl = '';
        $this->linkIcon = '';
        $this->linkIsActive = true;
        $this->linkSortOrder = 0;
        $this->resetValidation();
    }

    private function resetAlertForm(): void
    {
        $this->editingAlertId = null;
        $this->alertTitle = '';
        $this->alertMessage = '';
        $this->alertSeverity = 'medium';
        $this->alertStatus = 'active';
        $this->alertType = 'general';
        $this->alertSendPush = false;
        $this->alertStartsAt = null;
        $this->alertEndsAt = null;
        $this->resetValidation();
    }

    public function getOverviewStatsProperty(): array
    {
        return [
            'service_requests' => CitizenServiceRequest::count(),
            'active_requests' => CitizenServiceRequest::whereNotIn('status', ['completed', 'released', 'cancelled', 'rejected'])->count(),
            'open_grievances' => GrievanceReport::whereNotIn('status', ['resolved', 'closed'])->count(),
            'active_alerts' => EmergencyAlert::active()->count(),
            'open_sos' => SosAlert::where('status', 'open')->count(),
            'portal_links' => PublicServiceLink::where('is_active', true)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.citizen-services-manager', [
            'serviceLinks' => PublicServiceLink::orderBy('sort_order')->orderBy('title')->get(),
            'serviceRequests' => CitizenServiceRequest::with('resident:id,first_name,last_name,resident_id')->latest('status_updated_at')->limit(20)->get(),
            'grievances' => GrievanceReport::with('resident:id,first_name,last_name,resident_id')->latest()->limit(20)->get(),
            'sosAlerts' => SosAlert::with('resident:id,first_name,last_name,resident_id')->latest()->limit(20)->get(),
            'emergencyAlerts' => EmergencyAlert::latest()->limit(20)->get(),
        ]);
    }
}
