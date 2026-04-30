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

    public bool $showActionModal = false;
    public ?string $actionModalType = null;

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

    public ?int $editingRequestId = null;
    public string $requestStatus = 'submitted';
    public string $requestStep = '';
    public string $requestNote = '';

    public ?int $editingGrievanceId = null;
    public string $grievanceStatus = 'submitted';
    public string $grievanceResponse = '';

    public ?int $editingSosId = null;
    public string $sosStatus = 'open';

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

    public array $tabs = [
        'overview' => 'Overview',
        'links' => 'Portal Links',
        'requests' => 'Service Tracking',
        'grievances' => 'Grievances',
        'alerts' => 'Emergency Alerts',
        'sos' => 'SOS Alerts',
        'command-center' => 'Command Center',
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
            'requestStatus' => 'required|string|max:50',
            'requestStep' => 'nullable|string|max:255',
            'requestNote' => 'nullable|string',
            'grievanceStatus' => 'required|string|max:50',
            'grievanceResponse' => 'nullable|string',
            'sosStatus' => 'required|string|max:50',
            'commandCenterName' => 'required|string|max:255',
            'commandCenterHotline' => 'required|string|max:50',
            'commandCenterAlternateHotline' => 'nullable|string|max:50',
            'commandCenterEmail' => 'nullable|email|max:255',
        ];
    }

    public function changeTab(string $tab): void
    {
        $this->closeModals();
        $this->activeTab = $tab;
    }

    public function createLink(): void
    {
        $this->closeModals();
        $this->resetLinkForm();
        $this->actionModalType = 'link';
        $this->showActionModal = true;
    }

    public function editLink(int $id): void
    {
        $this->closeModals();
        $link = PublicServiceLink::findOrFail($id);

        $this->editingLinkId = $link->id;
        $this->linkTitle = $link->title;
        $this->linkServiceType = $link->service_type;
        $this->linkDescription = $link->description ?? '';
        $this->linkUrl = $link->url;
        $this->linkIcon = $link->icon ?? '';
        $this->linkIsActive = (bool) $link->is_active;
        $this->linkSortOrder = (int) $link->sort_order;
        $this->actionModalType = 'link';
        $this->showActionModal = true;
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
        $this->showActionModal = false;
        $this->actionModalType = null;
    }

    public function deleteLink(int $id): void
    {
        PublicServiceLink::findOrFail($id)->delete();
        $this->success('Portal link deleted successfully.');
    }

    public function createAlert(): void
    {
        $this->closeModals();
        $this->resetAlertForm();
        $this->actionModalType = 'alert';
        $this->showActionModal = true;
    }

    public function editAlert(int $id): void
    {
        $this->closeModals();
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
        $this->actionModalType = 'alert';
        $this->showActionModal = true;
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
        $this->showActionModal = false;
        $this->actionModalType = null;
    }

    public function editServiceRequest(int $id): void
    {
        $this->closeModals();
        $request = CitizenServiceRequest::findOrFail($id);

        $this->editingRequestId = $request->id;
        $this->requestStatus = $request->status;
        $this->requestStep = $request->current_step ?? '';
        $this->requestNote = $request->notes ?? '';
        $this->actionModalType = 'request';
        $this->showActionModal = true;
    }

    public function saveServiceRequest(): void
    {
        $this->validateOnly('requestStatus');
        $this->validateOnly('requestStep');
        $this->validateOnly('requestNote');

        $request = CitizenServiceRequest::findOrFail($this->editingRequestId);

        $request->update([
            'status' => $this->requestStatus,
            'current_step' => $this->requestStep ?: null,
            'notes' => $this->requestNote ?: null,
            'status_updated_at' => now(),
            'completed_at' => in_array($this->requestStatus, ['completed', 'released'], true) ? now() : null,
        ]);

        $this->success('Service request updated.');
        $this->resetRequestForm();
        $this->closeModals();
    }

    public function editGrievance(int $id): void
    {
        $this->closeModals();
        $grievance = GrievanceReport::findOrFail($id);

        $this->editingGrievanceId = $grievance->id;
        $this->grievanceStatus = $grievance->status;
        $this->grievanceResponse = $grievance->admin_response ?? '';
        $this->actionModalType = 'grievance';
        $this->showActionModal = true;
    }

    public function saveGrievance(): void
    {
        $this->validateOnly('grievanceStatus');
        $this->validateOnly('grievanceResponse');

        $grievance = GrievanceReport::findOrFail($this->editingGrievanceId);

        $grievance->update([
            'status' => $this->grievanceStatus,
            'admin_response' => $this->grievanceResponse ?: null,
            'resolved_at' => in_array($this->grievanceStatus, ['resolved', 'closed'], true) ? now() : null,
        ]);

        $this->success('Grievance updated.');
        $this->resetGrievanceForm();
        $this->closeModals();
    }

    public function editSos(int $id): void
    {
        $this->closeModals();
        $sos = SosAlert::findOrFail($id);

        $this->editingSosId = $sos->id;
        $this->sosStatus = $sos->status;
        $this->actionModalType = 'sos';
        $this->showActionModal = true;
    }

    public function saveSos(): void
    {
        $this->validateOnly('sosStatus');

        $sos = SosAlert::findOrFail($this->editingSosId);

        $sos->update([
            'status' => $this->sosStatus,
            'acknowledged_at' => in_array($this->sosStatus, ['acknowledged', 'resolved'], true) ? ($sos->acknowledged_at ?? now()) : null,
            'resolved_at' => $this->sosStatus === 'resolved' ? now() : null,
        ]);

        $this->success('SOS alert updated.');
        $this->resetSosForm();
        $this->closeModals();
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

    private function resetRequestForm(): void
    {
        $this->editingRequestId = null;
        $this->requestStatus = 'submitted';
        $this->requestStep = '';
        $this->requestNote = '';
        $this->resetValidation();
    }

    private function resetGrievanceForm(): void
    {
        $this->editingGrievanceId = null;
        $this->grievanceStatus = 'submitted';
        $this->grievanceResponse = '';
        $this->resetValidation();
    }

    private function resetSosForm(): void
    {
        $this->editingSosId = null;
        $this->sosStatus = 'open';
        $this->resetValidation();
    }

    private function closeModals(): void
    {
        $this->showActionModal = false;
        $this->actionModalType = null;
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
