<?php

namespace Tests\Feature;

use App\Livewire\Admin\AppReleaseManager;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AndroidApkMetadataReader;
use App\Services\MobileAppReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileAppPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_mobile_app_page_renders(): void
    {
        $this->get('/mobile-app')
            ->assertOk()
            ->assertSee('ProjectAccess Mobile')
            ->assertSee('Features')
            ->assertSee('mobile-app-theme-toggle')
            ->assertSee('Install Resident Portal')
            ->assertSee('fill="#0A84FF"', false)
            ->assertSee('resident-portal-install')
            ->assertSee('resident-portal/manifest.webmanifest', false)
            ->assertSee('resident-portal/device.js', false)
            ->assertSee('resident-portal/install.js', false)
            ->assertDontSee('Municipal Public Feedback System');

        $this->assertFileExists(public_path('resident-portal/sw.js'));
    }

    public function test_download_returns_latest_apk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('mobile-apps/test.apk', 'apk-bytes');

        app(MobileAppReleaseService::class)->saveDetails([
            'name' => 'ProjectAccess Mobile',
            'description' => 'Resident app.',
            'version_name' => '2.1.0',
            'version_code' => '21',
            'release_notes' => 'Bug fixes.',
            'features' => 'Requests',
            'source_project_path' => MobileAppReleaseService::SOURCE_PROJECT_PATH,
        ]);
        app(MobileAppReleaseService::class)->saveApk('mobile-apps/test.apk', 'signed.apk', 9);

        $this->get('/mobile-app/download')
            ->assertOk()
            ->assertDownload('projectaccess-2-1-0.apk')
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_release_metadata_exposes_current_version_and_download_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('mobile-apps/test.apk', 'apk-bytes');
        app(MobileAppReleaseService::class)->saveDetails([
            'name' => 'ProjectAccess Mobile',
            'description' => 'Resident app.',
            'version_name' => '2.1.0',
            'version_code' => '21',
            'release_notes' => 'Bug fixes.',
            'features' => 'Requests',
            'source_project_path' => MobileAppReleaseService::SOURCE_PROJECT_PATH,
        ]);
        app(MobileAppReleaseService::class)->saveApk('mobile-apps/test.apk', 'signed.apk', 9);

        $response = $this->getJson('/mobile-app/release.json')
            ->assertOk()
            ->assertJsonPath('version_name', '2.1.0')
            ->assertJsonPath('version_code', '21')
            ->assertJsonPath('has_apk', true)
            ->assertJsonPath('download_page_url', route('mobile-app.index'))
            ->assertJsonMissingPath('apk_path');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_download_404s_when_no_apk_exists(): void
    {
        $this->get('/mobile-app/download')
            ->assertNotFound();
    }

    public function test_app_release_admin_page_is_system_administrator_only(): void
    {
        $this->get('/admin/app-release')
            ->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/app-release')
            ->assertForbidden();

        $admin = User::factory()->create();
        Role::create(['name' => 'system-administrator', 'guard_name' => 'web']);
        $admin->assignRole('system-administrator');

        $this->actingAs($admin)
            ->get('/admin/app-release')
            ->assertOk()
            ->assertSee('App Release Manager');
    }

    public function test_selecting_and_uploading_apk_autofills_and_saves_embedded_version_metadata(): void
    {
        Storage::fake('public');
        $this->mock(AndroidApkMetadataReader::class, function (MockInterface $mock): void {
            $mock->shouldReceive('read')->twice()->andReturn([
                'version_name' => '3.2.1',
                'version_code' => '42',
            ]);
        });

        Livewire::test(AppReleaseManager::class)
            ->set('apk', UploadedFile::fake()->create('smartcity-access.apk', 100, 'application/vnd.android.package-archive'))
            ->assertSet('versionName', '3.2.1')
            ->assertSet('versionCode', '42')
            ->assertSee('Detected version 3.2.1 (build 42)')
            ->call('uploadApk')
            ->assertHasNoErrors();

        $this->assertSame('3.2.1', SystemSetting::get('mobile_app.version_name'));
        $this->assertSame('42', SystemSetting::get('mobile_app.version_code'));
        $this->assertTrue(app(MobileAppReleaseService::class)->release()['has_apk']);
    }
}
