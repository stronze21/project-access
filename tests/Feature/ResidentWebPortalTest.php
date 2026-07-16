<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResidentWebPortalTest extends TestCase
{
    use DatabaseTransactions;

    private const IOS_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        $this->withHeader('User-Agent', self::IOS_USER_AGENT);
    }

    public function test_non_ios_devices_cannot_access_the_web_portal(): void
    {
        $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/138.0 Safari/537.36'
        )->get('/resident-portal/login')
            ->assertForbidden()
            ->assertSee('You cannot open this page')
            ->assertSee('Alaminos City ACCESS');
    }

    public function test_ipados_desktop_user_agent_can_access_the_web_portal(): void
    {
        $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 Version/18.5 Mobile/15E148 Safari/604.1'
        )->get('/resident-portal/login')->assertOk();
    }

    public function test_android_devices_can_access_the_web_portal(): void
    {
        $this->withHeader(
            'User-Agent',
            'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 Chrome/138.0 Mobile Safari/537.36'
        )->get('/resident-portal/login')->assertOk();
    }

    public function test_resident_login_is_separate_from_staff_authentication(): void
    {
        $resident = $this->resident();

        $this->post('/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '123456',
        ])->assertRedirect('/resident-portal');

        $this->assertAuthenticatedAs($resident, 'resident');
        $this->assertGuest('web');
        $this->assertNotNull(session('resident_portal_expires_at'));
    }

    public function test_birthday_fallback_requires_mpin_update(): void
    {
        $resident = $this->resident(['mpin' => null, 'birth_date' => '1990-05-21']);

        $this->post('/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '900521',
        ])->assertRedirect('/resident-portal');

        $this->assertTrue(session('resident_portal_requires_mpin_update'));
    }

    public function test_inactive_resident_cannot_sign_in(): void
    {
        $resident = $this->resident(['is_active' => false]);

        $this->post('/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '123456',
        ])->assertSessionHasErrors('login');

        $this->assertGuest('resident');
    }

    public function test_expired_resident_session_is_logged_out(): void
    {
        $resident = $this->resident();

        $this->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->subMinute()])
            ->get('/resident-portal/profile')
            ->assertRedirect('/resident-portal/login');

        $this->assertGuest('resident');
    }

    public function test_portal_renders_original_mobile_brand_assets_and_navigation(): void
    {
        $resident = $this->resident();

        $this->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->addDays(60)])
            ->get('/resident-portal')
            ->assertOk()
            ->assertSee('City of Alaminos')
            ->assertSee('resident-portal/images/alaminos-seal.jpg', false)
            ->assertSee('resident-portal/images/access-logo.png', false)
            ->assertSee('Your Digital ID')
            ->assertSee('data-flip-card', false)
            ->assertSee('portal-id-last-name', false)
            ->assertSee('resident-portal/images/id-cards/access-id-back.jpg', false)
            ->assertDontSee('Your Resident QR');

        foreach ([
            'resident-portal/images/appicon.png',
            'resident-portal/images/splash.png',
            'resident-portal/images/bosesmoto-logo.jpg',
            'resident-portal/images/id-cards/access-id-front.png',
            'resident-portal/images/id-cards/access-id-back.jpg',
            'resident-portal/fonts/OpenSans-Regular.ttf',
        ] as $asset) {
            $this->assertFileExists(public_path($asset));
        }
    }

    public function test_digital_id_uses_local_photo_storage_and_has_a_clickable_back(): void
    {
        $resident = $this->resident([
            'photo_path' => 'resident-photos/local-resident.jpg',
            'middle_name' => 'Reyes',
            'signature' => 'data:image/png;base64,'.base64_encode('signature'),
        ]);

        $this->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->addDays(60)])
            ->get('/resident-portal/digital-id')
            ->assertOk()
            ->assertSee('src="/storage/resident-photos/local-resident.jpg"', false)
            ->assertSee('data-flip-card', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('resident-portal/images/id-cards/access-id-back.jpg', false)
            ->assertSee('portal-id-last-name', false)
            ->assertSee('portal-id-given-name', false)
            ->assertSee('portal-id-middle-name', false)
            ->assertSee('May 21, 1990')
            ->assertSee('Gender:')
            ->assertSee('AC-'.$resident->resident_id)
            ->assertSee('portal-id-signature', false)
            ->assertSee('Tap the card to view its back')
            ->assertDontSee('qr-detail', false)
            ->assertDontSee('Scan to verify resident record');
    }

    public function test_portal_does_not_authenticate_linked_citizen_into_staff_guard(): void
    {
        $resident = $this->resident();

        $this->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->addDays(60)])
            ->get('/resident-portal/bosesmoto')
            ->assertOk()
            ->assertSee('BosesMoTo');

        $this->assertGuest('web');
        $this->assertDatabaseHas('users', ['resident_id' => $resident->id]);
    }

    public function test_resident_can_update_signature_and_submit_portal_support_and_data_requests(): void
    {
        $resident = $this->resident();
        $session = ['resident_portal_expires_at' => now()->addDays(60)];

        $this->actingAs($resident, 'resident')->withSession($session)
            ->post('/resident-portal/profile/signature', ['signature' => 'data:image/png;base64,'.base64_encode('signature')])
            ->assertSessionHasNoErrors();

        $this->actingAs($resident, 'resident')->withSession($session)
            ->post('/resident-portal/actions/support', ['category' => 'technical', 'subject' => 'Portal help', 'message' => 'Please assist with my resident portal.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($resident, 'resident')->withSession($session)
            ->post('/resident-portal/actions/account-deletion', ['requested_action' => 'delete-app-data-only', 'retention_acknowledged' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('residents', ['id' => $resident->id, 'signature_status' => 'completed']);
        $this->assertDatabaseHas('support_requests', ['resident_id' => $resident->id, 'source' => 'resident-web']);
        $this->assertDatabaseHas('account_deletion_requests', ['resident_id' => $resident->id, 'source' => 'resident-web']);
    }

    private function resident(array $overrides = []): Resident
    {
        return Resident::create(array_merge([
            'resident_id' => 'R-WEB-'.str()->upper(str()->random(8)),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1990-05-21',
            'gender' => 'female',
            'civil_status' => 'single',
            'contact_number' => '09171234567',
            'email' => str()->random(8).'@example.test',
            'mpin' => Hash::make('123456'),
            'is_active' => true,
        ], $overrides));
    }
}
