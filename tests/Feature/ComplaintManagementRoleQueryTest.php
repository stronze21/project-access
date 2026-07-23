<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintManagementRoleQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_complaint_detail_loads_action_officers_from_spatie_roles(): void
    {
        Role::findOrCreate('system-administrator', 'web');
        Role::findOrCreate('action-officer', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $officer = User::factory()->create(['name' => 'Assigned Officer']);
        $officer->assignRole('action-officer');

        $category = ComplaintCategory::query()->create([
            'name' => 'Infrastructure',
            'slug' => 'infrastructure',
            'is_active' => true,
        ]);

        $complaint = Complaint::query()->create([
            'reference_code' => 'CMP-ROLE-QUERY-001',
            'is_anonymous_submission' => true,
            'title' => 'Damaged road',
            'short_summary' => 'A road needs repair.',
            'description' => 'The road surface is damaged and needs repair.',
            'category_id' => $category->id,
            'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
            'status' => Complaint::STATUS_RECEIVED,
            'priority' => Complaint::PRIORITY_MEDIUM,
        ]);

        $this->actingAs($admin)
            ->get(route('complaints.manage.show', $complaint))
            ->assertOk()
            ->assertSee('Assigned Officer');
    }

    public function test_bosesmoto_role_scopes_support_slug_role_names(): void
    {
        Role::findOrCreate('mayor', 'web');
        Role::findOrCreate('department-head', 'web');

        $mayor = User::factory()->create();
        $mayor->assignRole('mayor');

        $departmentHead = User::factory()->create();
        $departmentHead->assignRole('department-head');

        $this->assertTrue(User::query()->mayors()->whereKey($mayor)->exists());
        $this->assertTrue(User::query()->departmentHeads()->whereKey($departmentHead)->exists());
    }

    public function test_audit_search_uses_role_relationships_instead_of_a_users_role_column(): void
    {
        Role::findOrCreate('system-administrator', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin)
            ->get(route('complaints.audit.index', ['q' => 'action officer']))
            ->assertOk();
    }

    public function test_complaint_queue_and_detail_show_linked_resident_identity_to_admins(): void
    {
        Role::findOrCreate('system-administrator', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $resident = Resident::create([
            'resident_id' => 'PIN-COMPLAINANT-01', 'first_name' => 'Maria', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);
        $citizen = User::factory()->create(['resident_id' => $resident->id, 'name' => 'Citizen Account']);
        $category = ComplaintCategory::query()->create(['name' => 'Public Safety', 'slug' => 'public-safety', 'is_active' => true]);
        $complaint = Complaint::create([
            'reference_code' => 'CMP-IDENTITY-001', 'submitted_by_user_id' => $citizen->id,
            'is_anonymous_submission' => false, 'title' => 'Street light concern',
            'short_summary' => 'Street light is not working.', 'description' => 'The street light needs repair.',
            'category_id' => $category->id, 'visibility' => Complaint::VISIBILITY_PRIVATE,
            'status' => Complaint::STATUS_RECEIVED, 'priority' => Complaint::PRIORITY_MEDIUM,
        ]);

        $this->actingAs($admin)->get(route('complaints.manage.index'))
            ->assertOk()->assertSee('Maria')->assertSee('Santos')->assertSee('PIN-COMPLAINANT-01');
        $this->actingAs($admin)->get(route('complaints.manage.show', $complaint))
            ->assertOk()->assertSee('Resident ID/PIN')->assertSee('PIN-COMPLAINANT-01');
    }
}
