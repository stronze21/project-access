<?php

namespace Tests\Feature;

use App\Models\ComplaintBarangay;
use App\Models\Department;
use App\Models\SosAlert;
use App\Models\SosDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferenceDataManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('system-administrator', 'web');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system-administrator');
    }

    public function test_all_reference_management_pages_are_available(): void
    {
        foreach ([
            'complaints.references.index',
            'complaints.categories.index',
            'complaints.barangays.index',
            'complaints.departments.index',
            'complaints.action-officers.index',
            'complaints.officials.index',
            'complaints.sos-departments.index',
        ] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    public function test_barangays_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs($this->admin)->post(route('complaints.barangays.store'), [
            'name' => 'Poblacion',
            'code' => 'POB',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $barangay = ComplaintBarangay::query()->where('code', 'POB')->firstOrFail();

        $this->actingAs($this->admin)->put(route('complaints.barangays.update', $barangay), [
            'name' => 'Poblacion Proper',
            'code' => 'POB',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bosesmoto_barangays', [
            'id' => $barangay->id,
            'name' => 'Poblacion Proper',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('complaints.barangays.destroy', $barangay))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('bosesmoto_barangays', ['id' => $barangay->id]);
    }

    public function test_action_officer_page_creates_and_assigns_a_department_account(): void
    {
        $department = Department::query()->create(['name' => 'Public Safety', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('complaints.action-officers.store'), [
            'name' => 'Juan Responder',
            'email' => 'responder@example.test',
            'department_id' => $department->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertSessionHasNoErrors();

        $officer = User::query()->where('email', 'responder@example.test')->firstOrFail();

        $this->assertTrue($officer->isActionOfficer());
        $this->assertSame($department->id, $officer->department_id);

        $this->actingAs($this->admin)
            ->delete(route('complaints.action-officers.destroy', $officer))
            ->assertSessionHasNoErrors();

        $this->assertFalse($officer->fresh()->isActionOfficer());
        $this->assertDatabaseHas('users', ['id' => $officer->id]);
    }

    public function test_sos_departments_are_managed_and_linked_records_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->post(route('complaints.sos-departments.store'), [
            'name' => 'Fire Department',
            'code' => 'FIRE',
            'hotline' => '911',
            'description' => 'Fire and rescue response',
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $department = SosDepartment::query()->where('code', 'FIRE')->firstOrFail();
        SosAlert::query()->create([
            'reference_number' => 'SOS-REFERENCE-001',
            'status' => 'open',
            'sos_department_id' => $department->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('complaints.sos-departments.destroy', $department))
            ->assertSessionHasErrors('sos_department');

        $this->assertDatabaseHas('sos_departments', ['id' => $department->id]);
    }

    public function test_public_official_name_and_position_combination_must_be_unique(): void
    {
        $payload = [
            'name' => 'Maria Santos',
            'position' => 'City Mayor',
            'is_active' => '1',
        ];

        $this->actingAs($this->admin)
            ->post(route('complaints.officials.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('complaints.officials.store'), $payload)
            ->assertSessionHasErrors('position');
    }

    public function test_reference_pages_require_administrator_access(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('complaints.references.index'))
            ->assertForbidden();
    }
}
