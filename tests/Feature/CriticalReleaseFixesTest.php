<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use App\Services\ExportService;
use App\Services\QrCodeService;
use App\Services\XpPenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CriticalReleaseFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_card_api_requires_staff_authentication(): void
    {
        $resident = Resident::create([
            'resident_id' => 'R-SECURE-0001', 'first_name' => 'JUAN', 'last_name' => 'CRUZ',
            'birth_date' => '1990-01-01', 'gender' => 'male', 'is_active' => true,
        ]);

        $this->getJson("/api/residents/id-card/{$resident->id}")->assertUnauthorized();
        $this->getJson('/api/residents/id-card/search?q=JUAN')->assertUnauthorized();
        $this->postJson('/api/residents/id-card/batch', ['resident_ids' => [$resident->id]])->assertUnauthorized();
    }

    public function test_qr_service_generates_a_real_pdf(): void
    {
        Storage::fake('local');
        $resident = Resident::create([
            'resident_id' => 'R-PDF-0001', 'first_name' => 'MARIA', 'last_name' => 'SANTOS',
            'birth_date' => '1992-02-02', 'gender' => 'female', 'is_active' => true,
        ]);

        $path = app(QrCodeService::class)->generateResidentIdCard($resident);
        $contents = Storage::get($path);

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertGreaterThan(1000, strlen($contents));
    }

    public function test_exports_are_written_to_private_storage(): void
    {
        Storage::fake('local');

        $path = app(ExportService::class)->generateCsv([['JUAN', 'R-1']], ['Name', 'ID'], 'residents.csv');

        $this->assertSame('exports/residents.csv', $path);
        Storage::assertExists($path);
        $this->assertStringStartsWith("\xEF\xBB\xBF", Storage::get($path));
    }

    public function test_server_does_not_claim_a_signature_tablet_is_connected(): void
    {
        $this->assertFalse(app(XpPenService::class)->isTabletConnected());
    }

    public function test_literal_api_routes_are_not_captured_as_resource_ids(): void
    {
        $router = app('router');

        $this->assertStringEndsWith('@active', $router->getRoutes()->match(Request::create('/api/programs/active'))->getActionName());
        $this->assertStringEndsWith('@today', $router->getRoutes()->match(Request::create('/api/batches/today'))->getActionName());
        $this->assertStringEndsWith('@active', $router->getRoutes()->match(Request::create('/api/batches/active'))->getActionName());
        $this->assertStringEndsWith('@pendingSignatures', $router->getRoutes()->match(Request::create('/api/residents/pending-signatures'))->getActionName());
    }

    public function test_authenticated_staff_without_permission_cannot_access_resident_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/residents')->assertForbidden();
    }

    public function test_resident_can_reset_mpin_after_identity_verification(): void
    {
        $resident = Resident::create([
            'resident_id' => 'R-RESET-0001', 'first_name' => 'JUAN', 'last_name' => 'DELA CRUZ',
            'birth_date' => '1990-01-31', 'gender' => 'male', 'is_active' => true,
            'mpin' => Hash::make('111111'),
        ]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')->post(route('resident-portal.mpin.reset'), [
            'resident_id' => $resident->resident_id,
            'last_name' => 'dela cruz',
            'birth_date' => '1990-01-31',
            'mpin' => '654321',
            'mpin_confirmation' => '654321',
        ])->assertRedirect(route('resident-portal.login'));

        $this->assertTrue(Hash::check('654321', $resident->fresh()->mpin));
    }

    public function test_mpin_reset_rejects_mismatched_identity(): void
    {
        Resident::create([
            'resident_id' => 'R-RESET-0002', 'first_name' => 'MARIA', 'last_name' => 'SANTOS',
            'birth_date' => '1992-02-02', 'gender' => 'female', 'is_active' => true,
            'mpin' => Hash::make('111111'),
        ]);

        $this->postJson('/api/resident-portal/reset-mpin', [
            'resident_id' => 'R-RESET-0002', 'last_name' => 'WRONG', 'birth_date' => '1992-02-02',
            'mpin' => '654321', 'mpin_confirmation' => '654321',
        ])->assertUnprocessable();
    }
}
