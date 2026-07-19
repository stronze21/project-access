<?php

namespace Tests\Feature;

use App\Livewire\Admin\CitizenServicesManager;
use App\Models\CitizenServiceRequest;
use App\Models\CitizenServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CitizenServiceRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_requests_tab_lists_requests(): void
    {
        $request = CitizenServiceRequest::create([
            'service_type' => 'permit',
            'service_name' => 'Business Permit Renewal',
            'status' => 'submitted',
            'current_step' => 'Application received',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->set('activeTab', 'requests')
            ->assertSet('activeTab', 'requests')
            ->assertSee('Service Tracking Management')
            ->assertSee($request->reference_number)
            ->assertSee('Business Permit Renewal');
    }

    public function test_staff_can_update_a_service_request(): void
    {
        $request = CitizenServiceRequest::create([
            'service_type' => 'certificate',
            'service_name' => 'Certificate Request',
            'status' => 'submitted',
            'current_step' => 'Application received',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->call('editServiceRequest', $request->id)
            ->set('requestStatus', 'completed')
            ->set('requestStep', 'Ready for release')
            ->set('requestNote', 'Approved by the processing office.')
            ->set('requestExpectedCompletionAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveServiceRequest')
            ->assertHasNoErrors();

        $request->refresh();

        $this->assertSame('completed', $request->status);
        $this->assertSame('Ready for release', $request->current_step);
        $this->assertSame('Approved by the processing office.', $request->notes);
        $this->assertNotNull($request->completed_at);
        $this->assertNotNull($request->status_updated_at);
    }

    public function test_staff_can_create_and_update_a_service_type(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->set('activeTab', 'service-types')
            ->assertSee('Service Types')
            ->call('createServiceType')
            ->set('serviceTypeName', 'Building Clearance')
            ->set('serviceTypeCode', 'Building Clearance')
            ->set('serviceTypeDescription', 'Clearances issued by the engineering office.')
            ->set('serviceTypeSortOrder', 15)
            ->call('saveServiceType')
            ->assertHasNoErrors();

        $type = CitizenServiceType::where('code', 'building-clearance')->firstOrFail();

        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->call('editServiceType', $type->id)
            ->set('serviceTypeName', 'Building and Occupancy Clearance')
            ->set('serviceTypeIsActive', false)
            ->call('saveServiceType')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('citizen_service_types', [
            'id' => $type->id,
            'name' => 'Building and Occupancy Clearance',
            'is_active' => false,
        ]);
    }

    public function test_service_type_in_use_cannot_be_deleted(): void
    {
        $type = CitizenServiceType::where('code', 'certificate')->firstOrFail();
        CitizenServiceRequest::create([
            'service_type' => $type->code,
            'service_name' => 'Residency Certificate',
            'status' => 'submitted',
            'current_step' => 'Application received',
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->call('deleteServiceType', $type->id);

        $this->assertDatabaseHas('citizen_service_types', ['id' => $type->id]);
    }
}
