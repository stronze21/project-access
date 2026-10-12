<?php

namespace Tests\Feature;

use App\Livewire\ResidentPhotoSignatureManager;
use App\Models\Resident;
use App\Models\User;
use App\Observers\ResidentObserver;
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
            ->assertSee('Required filename: {resident_id}_signature.jpg, .jpeg, or .png')
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
            ->assertSee('ready to stage for automatic mapping')
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
            ->assertSee('Signature filename must be {resident_id}_signature.jpg, .jpeg, or .png.')
            ->call('importSignatures')
            ->assertHasNoErrors();

        $resident->refresh();
        $this->assertStringStartsWith('data:image/png;base64,', $resident->signature);
        $this->assertSame('verified', $resident->signature_status);
        Storage::disk('public')->assertExists('resident-signatures/R-PHOTO-001_signature.png');
    }

    public function test_it_stages_unknown_media_and_maps_it_when_the_resident_is_created(): void
    {
        $user = $this->authorizedUser();

        Livewire::actingAs($user)
            ->test(ResidentPhotoSignatureManager::class)
            ->set('photoFiles', [
                UploadedFile::fake()->image('R-FUTURE-001.jpg'),
            ])
            ->assertSee('ready to stage for automatic mapping')
            ->call('importPhotos')
            ->assertSee('Staged until this resident is registered')
            ->set('signatureFiles', [
                UploadedFile::fake()->image('R-FUTURE-001_signature.png'),
            ])
            ->call('importSignatures')
            ->assertSee('Staged until this resident is registered')
            ->assertHasNoErrors();

        Storage::disk('public')->assertExists('resident-media-staging/photos/R-FUTURE-001.jpg');
        Storage::disk('public')->assertExists('resident-media-staging/signatures/R-FUTURE-001_signature.png');

        $resident = $this->resident('R-FUTURE-001', 'Future', 'Resident');

        // DatabaseTransactions keeps the test transaction open, so invoke the
        // after-commit observer directly to exercise the production mapping.
        app(ResidentObserver::class)->created($resident);
        $resident->refresh();

        $this->assertSame('resident-photos/R-FUTURE-001.jpg', $resident->photo_path);
        $this->assertStringStartsWith('data:image/png;base64,', $resident->signature);
        $this->assertSame('verified', $resident->signature_status);
        Storage::disk('public')->assertExists('resident-photos/R-FUTURE-001.jpg');
        Storage::disk('public')->assertExists('resident-signatures/R-FUTURE-001_signature.png');
        Storage::disk('public')->assertMissing('resident-media-staging/photos/R-FUTURE-001.jpg');
        Storage::disk('public')->assertMissing('resident-media-staging/signatures/R-FUTURE-001_signature.png');
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

    public function test_it_accepts_ten_megabyte_photos_and_resizes_large_dimensions(): void
    {
        $resident = $this->resident('26-00001', 'Large', 'Photo');
        $photo = UploadedFile::fake()->image('26-00001.jpg', 2400, 1800)->size(10240);

        Livewire::actingAs($this->authorizedUser())
            ->test(ResidentPhotoSignatureManager::class)
            ->set('photoFiles', [$photo])
            ->assertSee('Ready to import')
            ->call('importPhotos')
            ->assertSee('Imported successfully')
            ->assertHasNoErrors();

        $storedPath = Storage::disk('public')->path($resident->fresh()->photo_path);
        [$width, $height] = getimagesize($storedPath);
        $this->assertLessThanOrEqual(1600, max($width, $height));
    }

    public function test_mixed_signature_batch_converts_stages_rejects_duplicates_and_is_repeatable(): void
    {
        $resident = $this->resident('BATCH-1', 'Batch', 'Resident');
        $resident->update(['signature' => 'old-signature']);
        $component = Livewire::actingAs($this->authorizedUser())
            ->test(ResidentPhotoSignatureManager::class)
            ->set('signatureFiles', [
                UploadedFile::fake()->createWithContent('BATCH-1_signature.jpg', 'not an image'),
                UploadedFile::fake()->image('BATCH-1_SIGNATURE.JPG'),
                UploadedFile::fake()->image('BATCH-1_signature.png'),
                UploadedFile::fake()->image('FUTURE-1_signature.jpeg'),
                UploadedFile::fake()->image('FUTURE-2_signature.jpg'),
                UploadedFile::fake()->image('BATCH-2_signature.JPEG'),
            ]);
        $second = $this->resident('BATCH-2', 'Second', 'Resident');
        $component->call('importSignatures');
        $component
            ->assertSet('signatureResults.0.status', 'failed')
            ->assertSet('signatureResults.1.status', 'imported')
            ->assertSet('signatureResults.2.status', 'failed')
            ->assertSet('signatureResults.3.status', 'staged')
            ->assertSet('signatureResults.4.status', 'staged')
            ->assertSet('signatureResults.5.status', 'imported');
        foreach ([$resident, $second] as $existing) {
            $bytes = Storage::disk('public')->get("resident-signatures/{$existing->resident_id}_signature.png");
            $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($bytes)[2]);
            $this->assertSame('data:image/png;base64,'.base64_encode($bytes), $existing->fresh()->signature);
        }
        foreach (['FUTURE-1', 'FUTURE-2'] as $id) {
            $path = "resident-media-staging/signatures/{$id}_signature.png";
            $bytes = Storage::disk('public')->get($path);
            $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($bytes)[2]);
            $future = $this->resident($id, 'Future', 'Resident');
            app(ResidentObserver::class)->created($future);
            $this->assertSame('data:image/png;base64,'.base64_encode($bytes), $future->fresh()->signature);
            Storage::disk('public')->assertMissing($path);
        }
        $this->mock(\App\Services\ResidentSignatureProcessor::class)->shouldNotReceive('store');
        $component->call('importSignatures')->assertSet('signatureResults.3.status', 'staged');
    }

    public function test_signature_failure_preserves_previous_signature_and_continues_batch(): void
    {
        $resident = $this->resident('FAIL-1', 'Failed', 'Resident');
        $resident->update(['signature' => 'previous', 'signature_status' => 'verified']);
        Storage::disk('public')->put('resident-signatures/FAIL-1_signature.png', 'previous-file');
        $processor = $this->partialMock(\App\Services\ResidentSignatureProcessor::class);
        $processor->shouldReceive('convert')->once()->andThrow(new \RuntimeException('Conversion failed'));
        $processor->shouldReceive('convert')->once()->passthru();
        Livewire::actingAs($this->authorizedUser())->test(ResidentPhotoSignatureManager::class)
            ->set('signatureFiles', [UploadedFile::fake()->image('FAIL-1_signature.jpg'), UploadedFile::fake()->image('OK-1_signature.png')])
            ->call('importSignatures')
            ->assertSet('signatureResults.0.status', 'failed')
            ->assertSet('signatureResults.1.status', 'staged');
        $this->assertSame('previous', $resident->fresh()->signature);
        $this->assertSame('previous-file', Storage::disk('public')->get('resident-signatures/FAIL-1_signature.png'));
    }

    public function test_storage_failure_does_not_update_resident_and_next_file_is_processed(): void
    {
        $resident = $this->resident('WRITE-1', 'Write', 'Resident');
        $resident->update(['signature' => 'previous']);
        $disk = Storage::disk('public');
        $mockDisk = \Mockery::mock($disk);
        $mockDisk->shouldReceive('put')->with('resident-signatures/WRITE-1_signature.png', \Mockery::type('string'))->once()->andReturn(false);
        $mockDisk->shouldReceive('put')->with('resident-media-staging/signatures/WRITE-2_signature.png', \Mockery::type('string'))->once()->andReturn(true);
        Storage::set('public', $mockDisk);
        Livewire::actingAs($this->authorizedUser())->test(ResidentPhotoSignatureManager::class)
            ->set('signatureFiles', [UploadedFile::fake()->image('WRITE-1_signature.jpg'), UploadedFile::fake()->image('WRITE-2_signature.jpeg')])
            ->call('importSignatures')->assertSet('signatureResults.0.status', 'failed')->assertSet('signatureResults.1.status', 'staged');
        $this->assertSame('previous', $resident->fresh()->signature);
    }

    public function test_signature_batch_limits(): void
    {
        $component = Livewire::actingAs($this->authorizedUser())->test(ResidentPhotoSignatureManager::class);
        $files = [];
        for ($i = 0; $i < 20; $i++) {
            $files[] = UploadedFile::fake()->image("LIMIT-{$i}_signature.png")->size(3072);
        }
        $component->set('signatureFiles', $files)->assertHasNoErrors()->call('importSignatures');
        $this->assertCount(20, Storage::disk('public')->allFiles('resident-media-staging/signatures'));
        $files[] = UploadedFile::fake()->image('LIMIT-20_signature.png');
        $component->set('signatureFiles', $files)->assertHasErrors('signatureFiles');
        array_pop($files);
        $files[0] = UploadedFile::fake()->image('LIMIT-0_signature.png')->size(3073);
        $component->set('signatureFiles', $files)->assertHasErrors('signatureFiles');
        $component->set('signatureFiles', [UploadedFile::fake()->image('SIZE_signature.jpg')->size(10240)])
            ->assertHasNoErrors()->assertSet('signatureResults.0.valid', true);
        $component->set('signatureFiles', [UploadedFile::fake()->image('SIZE_signature.jpg')->size(10241)])
            ->assertSet('signatureResults.0.valid', false);
    }

    public function test_signature_processor_preserves_ink_alpha_and_dimensions(): void
    {
        $image = imagecreatetruecolor(4, 1);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        foreach ([[255, 255, 255, 0], [245, 245, 245, 0], [20, 30, 40, 0], [40, 50, 60, 75]] as $x => $rgba) {
            imagesetpixel($image, $x, 0, imagecolorallocatealpha($image, ...$rgba));
        }
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $output = app(\App\Services\ResidentSignatureProcessor::class)->convert(UploadedFile::fake()->createWithContent('alpha.png', $bytes));
        $decoded = imagecreatefromstring($output);
        $this->assertSame(4, imagesx($decoded));
        $this->assertSame(1, imagesy($decoded));
        $this->assertSame(127, imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha']);
        $this->assertSame(127, imagecolorsforindex($decoded, imagecolorat($decoded, 1, 0))['alpha']);
        $this->assertSame(['red' => 20, 'green' => 30, 'blue' => 40, 'alpha' => 0], imagecolorsforindex($decoded, imagecolorat($decoded, 2, 0)));
        $this->assertSame(75, imagecolorsforindex($decoded, imagecolorat($decoded, 3, 0))['alpha']);
        imagedestroy($decoded);
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
