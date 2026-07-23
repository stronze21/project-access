<?php

namespace Tests\Feature;

use App\Livewire\ResidentIdentityChangeRequests;
use App\Models\Resident;
use App\Models\ResidentIdentityChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ResidentIdentityChangeRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        Permission::firstOrCreate(['name' => 'edit-residents', 'guard_name' => 'web']);
    }

    public function test_approval_is_the_only_step_that_replaces_a_signature(): void
    {
        $resident = $this->resident(['signature' => 'old-signature', 'signature_status' => 'completed']);
        $request = ResidentIdentityChangeRequest::create([
            'resident_id' => $resident->id,
            'type' => 'signature',
            'requested_signature' => 'data:image/png;base64,'.base64_encode('new-signature'),
            'request_reason' => 'My legal signature has changed.',
        ]);
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo('edit-residents');

        $this->assertSame('old-signature', $resident->fresh()->signature);

        Livewire::actingAs($reviewer)
            ->test(ResidentIdentityChangeRequests::class)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertSame('data:image/png;base64,'.base64_encode('new-signature'), $resident->fresh()->signature);
        $this->assertDatabaseHas('resident_identity_change_requests', [
            'id' => $request->id, 'status' => 'approved', 'reviewed_by' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('resident_notifications', ['resident_id' => $resident->id, 'type' => 'identity-change-request']);
    }

    public function test_denial_requires_and_records_a_reason_without_changing_identity_media(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('identity-change-requests/photo.jpg', 'proposed');
        $resident = $this->resident(['photo_path' => 'resident-photos/current.jpg']);
        $request = ResidentIdentityChangeRequest::create([
            'resident_id' => $resident->id,
            'type' => 'photo',
            'requested_file_path' => 'identity-change-requests/photo.jpg',
            'request_reason' => 'My appearance has significantly changed.',
        ]);
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo('edit-residents');

        Livewire::actingAs($reviewer)
            ->test(ResidentIdentityChangeRequests::class)
            ->set('denyingRequestId', $request->id)
            ->call('deny')
            ->assertHasErrors(['denialReason'])
            ->set('denialReason', 'Identity documents did not match the submitted image.')
            ->call('deny')
            ->assertHasNoErrors();

        $this->assertSame('resident-photos/current.jpg', $resident->fresh()->photo_path);
        $this->assertDatabaseHas('resident_identity_change_requests', [
            'id' => $request->id,
            'status' => 'denied',
            'review_reason' => 'Identity documents did not match the submitted image.',
        ]);
        Storage::disk('local')->assertMissing('identity-change-requests/photo.jpg');
    }

    private function resident(array $overrides = []): Resident
    {
        return Resident::create(array_merge([
            'resident_id' => 'R-IDR-'.str()->upper(str()->random(8)),
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
        ], $overrides));
    }
}
