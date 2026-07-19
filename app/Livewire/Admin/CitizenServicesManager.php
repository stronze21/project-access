<?php

namespace App\Livewire\Admin;

use App\Models\CitizenServiceRequest;
use App\Models\CitizenServiceType;
use App\Models\Complaint;
use App\Models\EmergencyAlert;
use App\Models\Poll;
use App\Models\PublicServiceLink;
use App\Models\SentimentPost;
use App\Models\SosAlert;
use App\Models\SystemSetting;
use App\Services\PushNotificationService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
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

    public ?int $editingSosId = null;

    public ?int $editingRequestId = null;

    public ?int $editingServiceTypeId = null;

    public string $serviceTypeCode = '';

    public string $serviceTypeName = '';

    public string $serviceTypeDescription = '';

    public bool $serviceTypeIsActive = true;

    public int $serviceTypeSortOrder = 0;

    public string $requestStatus = 'submitted';

    public string $requestStep = 'Application received';

    public string $requestNote = '';

    public ?string $requestExpectedCompletionAt = null;

    public string $sosStatus = 'open';

    public string $commandCenterName = '';

    public string $commandCenterHotline = '';

    public string $commandCenterAlternateHotline = '';

    public string $commandCenterEmail = '';

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

    public array $sosStatusOptions = [
        ['id' => 'open', 'name' => 'Open'],
        ['id' => 'acknowledged', 'name' => 'Acknowledged'],
        ['id' => 'resolved', 'name' => 'Resolved'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
    ];

    public array $serviceStatusOptions = [
        ['id' => 'submitted', 'name' => 'Submitted'],
        ['id' => 'reviewing', 'name' => 'Reviewing'],
        ['id' => 'processing', 'name' => 'Processing'],
        ['id' => 'for-release', 'name' => 'For Release'],
        ['id' => 'completed', 'name' => 'Completed'],
        ['id' => 'released', 'name' => 'Released'],
        ['id' => 'cancelled', 'name' => 'Cancelled'],
        ['id' => 'rejected', 'name' => 'Rejected'],
    ];

    public array $tabs = [
        'overview' => 'Overview',
        'requests' => 'Service Requests',
        'service-types' => 'Service Types',
        'links' => 'Portal Links',
        'alerts' => 'Emergency Alerts',
        'sos' => 'SOS Alerts',
        'command-center' => 'Command Center',
    ];

    public function mount(): void
    {
        $this->loadCommandCenterSettings();
        $this->primeFormMaps();
        $this->normalizeActiveTab();
    }

    protected function rules(): array
    {
        return [
            'linkTitle' => 'required|string|max:255',
            'linkServiceType' => 'required|exists:citizen_service_types,code',
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
            'sosStatus' => 'required|string|max:50',
            'requestStatus' => 'required|in:submitted,reviewing,processing,for-release,completed,released,cancelled,rejected',
            'requestStep' => 'required|string|max:150',
            'requestNote' => 'nullable|string|max:5000',
            'requestExpectedCompletionAt' => 'nullable|date',
            'serviceTypeName' => 'required|string|max:255',
            'serviceTypeDescription' => 'nullable|string|max:2000',
            'serviceTypeIsActive' => 'boolean',
            'serviceTypeSortOrder' => 'required|integer|min:0',
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
        $this->normalizeActiveTab();
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

    public function editServiceRequest(int $id): void
    {
        $this->closeModals();
        $serviceRequest = CitizenServiceRequest::findOrFail($id);

        $this->editingRequestId = $serviceRequest->id;
        $this->requestStatus = $serviceRequest->status;
        $this->requestStep = $serviceRequest->current_step ?? '';
        $this->requestNote = $serviceRequest->notes ?? '';
        $this->requestExpectedCompletionAt = $serviceRequest->expected_completion_at?->format('Y-m-d\TH:i');
        $this->actionModalType = 'request';
        $this->showActionModal = true;
    }

    public function saveServiceRequest(): void
    {
        $this->validateOnly('requestStatus');
        $this->validateOnly('requestStep');
        $this->validateOnly('requestNote');
        $this->validateOnly('requestExpectedCompletionAt');

        $serviceRequest = CitizenServiceRequest::findOrFail($this->editingRequestId);
        $isComplete = in_array($this->requestStatus, ['completed', 'released'], true);

        $serviceRequest->update([
            'status' => $this->requestStatus,
            'current_step' => $this->requestStep,
            'notes' => $this->requestNote ?: null,
            'expected_completion_at' => $this->requestExpectedCompletionAt ?: null,
            'completed_at' => $isComplete ? ($serviceRequest->completed_at ?? now()) : null,
            'status_updated_at' => now(),
        ]);

        $this->success('Service request updated.');
        $this->resetServiceRequestForm();
        $this->closeModals();
    }

    public function createServiceType(): void
    {
        $this->closeModals();
        $this->resetServiceTypeForm();
        $this->actionModalType = 'service-type';
        $this->showActionModal = true;
    }

    public function editServiceType(int $id): void
    {
        $this->closeModals();
        $serviceType = CitizenServiceType::findOrFail($id);

        $this->editingServiceTypeId = $serviceType->id;
        $this->serviceTypeCode = $serviceType->code;
        $this->serviceTypeName = $serviceType->name;
        $this->serviceTypeDescription = $serviceType->description ?? '';
        $this->serviceTypeIsActive = $serviceType->is_active;
        $this->serviceTypeSortOrder = $serviceType->sort_order;
        $this->actionModalType = 'service-type';
        $this->showActionModal = true;
    }

    public function saveServiceType(): void
    {
        $this->serviceTypeCode = str($this->serviceTypeCode)->slug()->toString();

        $this->validate([
            'serviceTypeCode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('citizen_service_types', 'code')->ignore($this->editingServiceTypeId),
            ],
            'serviceTypeName' => 'required|string|max:255',
            'serviceTypeDescription' => 'nullable|string|max:2000',
            'serviceTypeIsActive' => 'boolean',
            'serviceTypeSortOrder' => 'required|integer|min:0',
        ]);

        CitizenServiceType::updateOrCreate(
            ['id' => $this->editingServiceTypeId],
            [
                'code' => $this->serviceTypeCode,
                'name' => $this->serviceTypeName,
                'description' => $this->serviceTypeDescription ?: null,
                'is_active' => $this->serviceTypeIsActive,
                'sort_order' => $this->serviceTypeSortOrder,
            ]
        );

        $this->success($this->editingServiceTypeId ? 'Service type updated.' : 'Service type created.');
        $this->resetServiceTypeForm();
        $this->closeModals();
    }

    public function deleteServiceType(int $id): void
    {
        $serviceType = CitizenServiceType::findOrFail($id);
        $isUsed = CitizenServiceRequest::where('service_type', $serviceType->code)->exists()
            || PublicServiceLink::where('service_type', $serviceType->code)->exists();

        if ($isUsed) {
            $this->error('This service type is in use and cannot be deleted. Deactivate it instead.');

            return;
        }

        $serviceType->delete();
        $this->success('Service type deleted.');
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

    private function primeFormMaps(): void {}

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

    private function resetSosForm(): void
    {
        $this->editingSosId = null;
        $this->sosStatus = 'open';
        $this->resetValidation();
    }

    private function resetServiceRequestForm(): void
    {
        $this->editingRequestId = null;
        $this->requestStatus = 'submitted';
        $this->requestStep = 'Application received';
        $this->requestNote = '';
        $this->requestExpectedCompletionAt = null;
        $this->resetValidation();
    }

    private function resetServiceTypeForm(): void
    {
        $this->editingServiceTypeId = null;
        $this->serviceTypeCode = '';
        $this->serviceTypeName = '';
        $this->serviceTypeDescription = '';
        $this->serviceTypeIsActive = true;
        $this->serviceTypeSortOrder = 0;
        $this->resetValidation();
    }

    private function closeModals(): void
    {
        $this->showActionModal = false;
        $this->actionModalType = null;
    }

    private function normalizeActiveTab(): void
    {
        if (! array_key_exists($this->activeTab, $this->tabs)) {
            $this->activeTab = 'overview';
        }
    }

    public function getOverviewStatsProperty(): array
    {
        return [
            'open_complaints' => Complaint::whereIn('status', [
                Complaint::STATUS_RECEIVED,
                Complaint::STATUS_ASSIGNED,
                Complaint::STATUS_IN_PROGRESS,
            ])->count(),
            'active_alerts' => EmergencyAlert::active()->count(),
            'open_sos' => SosAlert::where('status', 'open')->count(),
            'portal_links' => PublicServiceLink::where('is_active', true)->count(),
            'polls' => Poll::count(),
            'sentiment_posts' => SentimentPost::count(),
        ];
    }

    public function render()
    {
        $this->normalizeActiveTab();

        $sosMapAlerts = $this->activeTab === 'sos'
            ? SosAlert::query()
                ->with([
                    'resident:id,first_name,last_name,resident_id',
                    'department:id,name,code,hotline',
                ])
                ->whereIn('status', ['open', 'acknowledged'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->oldest()
                ->get()
                ->map(fn (SosAlert $alert): array => [
                    'id' => $alert->id,
                    'reference' => $alert->reference_number,
                    'status' => $alert->status,
                    'latitude' => (float) $alert->latitude,
                    'longitude' => (float) $alert->longitude,
                    'resident' => $alert->resident?->full_name ?? 'Unknown resident',
                    'contact' => $alert->contact_number,
                    'location' => $alert->location_label,
                    'department' => $alert->department?->name,
                    'reported_at' => $alert->created_at?->format('M d, Y h:i A'),
                ])
                ->values()
                ->all()
            : [];

        return view('livewire.admin.citizen-services-manager', [
            'serviceRequests' => CitizenServiceRequest::with('resident')
                ->latest('status_updated_at')
                ->limit(50)
                ->get(),
            'serviceTypes' => CitizenServiceType::orderBy('sort_order')->orderBy('name')->get(),
            'linkServiceTypes' => CitizenServiceType::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (CitizenServiceType $type): array => ['id' => $type->code, 'name' => $type->name])
                ->all(),
            'serviceLinks' => PublicServiceLink::orderBy('sort_order')->orderBy('title')->get(),
            'sosAlerts' => SosAlert::with([
                'resident:id,first_name,last_name,resident_id',
                'department:id,name,code,hotline',
            ])->latest()->limit(20)->get(),
            'sosMapAlerts' => $sosMapAlerts,
            'emergencyAlerts' => EmergencyAlert::latest()->limit(20)->get(),
        ]);
    }
}
