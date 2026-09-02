<?php

namespace Tests\Feature;

use App\Livewire\ResidentList;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResidentListSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_resident_search_finds_an_active_resident_by_pin(): void
    {
        $matchingResident = $this->resident('26-45287', 'Maria', 'Santos');
        $otherResident = $this->resident('26-99999', 'Juan', 'Dela Cruz');

        Livewire::actingAs($this->authorizedUser())
            ->test(ResidentList::class)
            ->set('search', '26-45287')
            ->assertSee($matchingResident->resident_id)
            ->assertSee($matchingResident->full_name)
            ->assertDontSee($otherResident->resident_id);
    }

    public function test_select_all_adds_every_resident_on_the_current_page(): void
    {
        Livewire::actingAs($this->authorizedUser())
            ->test(ResidentList::class)
            ->set('selectedResidents', [99])
            ->call('toggleSelectAll', [1, 2, 3])
            ->assertSet('selectedResidents', [99, 1, 2, 3]);
    }

    public function test_select_all_clears_only_the_current_page_when_it_is_already_selected(): void
    {
        Livewire::actingAs($this->authorizedUser())
            ->test(ResidentList::class)
            ->set('selectedResidents', [99, 1, 2, 3])
            ->call('toggleSelectAll', [1, 2, 3])
            ->assertSet('selectedResidents', [99]);
    }

    private function authorizedUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'view-residents', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('view-residents');

        return $user;
    }

    private function resident(string $residentId, string $firstName, string $lastName): Resident
    {
        return Resident::create([
            'resident_id' => $residentId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
        ]);
    }
}
