<?php

namespace Tests\Feature;

use App\Livewire\ResidentPhotoSignatureManager;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResidentPhotoSignatureManagerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        Storage::fake('public');
    }

    public function test_authorized_user_can_open_manager_from_resident_import_export_menu(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('residents.index'))
            ->assertOk()
            ->assertSee(route('residents.photo-signature-manager'), false)
            ->assertSee('Photo and Signature Manager');

        $this->actingAs($user)
            ->get(route('residents.photo-signature-manager'))
            ->assertOk()
            ->assertSee('Required filename: {resident_id}.jpg, {resident_id}.jpeg, or {resident_id}.png')
            ->assertSee('Required filename: {resident_id}_signature.png')
            ->assertSee('up to 20 JPG/JPEG/PNG files')
            ->assertSee('60 MB total');
    }

    public function test_it_validates_previews_and_imports_valid_photos_and_signatures(): void
    {
        $user = $this->authorizedUser();
        $resident = $this->resident('R-PHOTO-001', 'Maria', 'Santos');

        $component = Livewire::actingAs($user)
            ->test(ResidentPhotoSignatureManager::class)
            ->set('photoFiles', [
                UploadedFile::fake()->image('R-PHOTO-001.jpeg'),
                UploadedFile::fake()->image('UNKNOWN.jpg'),
            ])
            ->assertSee($resident->full_name)
            ->assertSee('No resident matches this filename.')
            ->call('importPhotos')
            ->assertHasNoErrors();

        $this->assertSame('resident-photos/R-PHOTO-001.jpeg', $resident->fresh()->photo_path);
        Storage::disk('public')->assertExists('resident-photos/R-PHOTO-001.jpeg');

        $component
            ->set('signatureFiles', [
                UploadedFile::fake()->image('R-PHOTO-001_signature.png'),
                UploadedFile::fake()->image('R-PHOTO-001_wrong.png'),
            ])
            ->assertSee($resident->full_name)
            ->assertSee('Signature filename must be {resident_id}_signature.png.')
            ->call('importSignatures')
            ->assertHasNoErrors();

        $resident->refresh();
        $this->assertStringStartsWith('data:image/png;base64,', $resident->signature);
        $this->assertSame('verified', $resident->signature_status);
        Storage::disk('public')->assertExists('resident-signatures/R-PHOTO-001_signature.png');
    }

    public function test_user_without_import_permission_cannot_open_manager(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('residents.photo-signature-manager'))
            ->assertForbidden();
    }

    public function test_it_rejects_batches_larger_than_twenty_files(): void
    {
        $files = [];
        for ($index = 1; $index <= 21; $index++) {
            $files[] = UploadedFile::fake()->create("R-BATCH-{$index}.png", 1, 'image/png');
        }

        Livewire::actingAs($this->authorizedUser())
            ->test(ResidentPhotoSignatureManager::class)
            ->set('photoFiles', $files)
            ->assertHasErrors('photoFiles')
            ->assertSee('no more than 20 photo files');
    }

    private function authorizedUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view-residents', 'import-residents'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['view-residents', 'import-residents']);

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
