<?php

namespace Tests\Feature;

use App\Exceptions\BhwisUnavailableException;
use App\Models\Resident;
use App\Models\ResidentEmailVerificationCode;
use App\Services\Bhwis\BhwisGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class BhwisResidentActivationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true]);
        RateLimiter::clear('activation:ip:'.hash('sha256', '127.0.0.1'));
    }

    public function test_api_requires_all_three_consents_before_lookup(): void
    {
        $this->postJson('/api/resident-portal/register', [
            'resident_id' => 'PIN-1', 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => 'resident@example.test', 'email_challenge_id' => (string) str()->uuid(), 'email_code' => '123456',
            'mpin' => '123456', 'mpin_confirmation' => '123456',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['terms_accepted', 'privacy_notice_acknowledged', 'bhwis_import_consented']);

        $this->assertDatabaseCount('activation_consent_audits', 0);
    }

    public function test_existing_local_resident_is_activated_without_querying_bhwis(): void
    {
        $resident = Resident::create([
            'resident_id' => 'PIN-LOCAL', 'first_name' => 'Maria', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single',
            'is_active' => true,
        ]);
        $gateway = Mockery::mock(BhwisGateway::class);
        $gateway->shouldNotReceive('findResident');
        $gateway->shouldNotReceive('linkedRecords');
        $this->app->instance(BhwisGateway::class, $gateway);

        $this->postJson('/api/resident-portal/register', $this->payload('PIN-LOCAL'))
            ->assertCreated()->assertJsonPath('resident.resident_id', 'PIN-LOCAL');

        $this->assertTrue(Hash::check('123456', $resident->fresh()->mpin));
        $this->assertDatabaseHas('activation_consent_audits', [
            'resident_id' => $resident->id, 'channel' => 'api', 'outcome' => 'activated',
            'terms_version' => config('bhwis.consent_versions.terms'),
        ]);
    }

    public function test_local_identity_mismatch_does_not_fall_back_to_bhwis(): void
    {
        Resident::create([
            'resident_id' => 'PIN-LOCAL', 'first_name' => 'Maria', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single',
            'is_active' => true,
        ]);
        $gateway = Mockery::mock(BhwisGateway::class);
        $gateway->shouldNotReceive('findResident');
        $gateway->shouldNotReceive('linkedRecords');
        $this->app->instance(BhwisGateway::class, $gateway);

        $payload = $this->payload('PIN-LOCAL');
        $payload['last_name'] = 'Incorrect';
        ResidentEmailVerificationCode::where('challenge_id', $payload['email_challenge_id'])->update(['last_name' => 'Incorrect']);

        $this->postJson('/api/resident-portal/register', $payload)
            ->assertNotFound()
            ->assertJsonValidationErrors('resident_id');

        $this->assertDatabaseHas('activation_consent_audits', [
            'resident_identifier' => 'PIN-LOCAL', 'outcome' => 'identity_mismatch',
        ]);
    }

    public function test_nonlocal_resident_gets_retryable_error_when_bhwis_is_unavailable(): void
    {
        $gateway = Mockery::mock(BhwisGateway::class);
        $gateway->shouldReceive('findResident')->once()
            ->andThrow(new BhwisUnavailableException('BHWIS resident lookup failed.'));
        $this->app->instance(BhwisGateway::class, $gateway);

        $this->postJson('/api/resident-portal/register', $this->payload('PIN-MISSING'))
            ->assertStatus(503)->assertJsonPath('retryable', true);

        $this->assertDatabaseHas('activation_consent_audits', [
            'resident_identifier' => 'PIN-MISSING', 'outcome' => 'bhwis_unavailable',
        ]);
        $this->assertDatabaseMissing('residents', ['resident_id' => 'PIN-MISSING']);
    }

    public function test_bhwis_import_is_atomic_and_links_only_existing_family_members(): void
    {
        $relative = Resident::create([
            'resident_id' => 'PIN-RELATIVE', 'first_name' => 'Ana', 'last_name' => 'Santos',
            'birth_date' => '1980-01-01', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);
        $personal = [
            'PIN' => 'PIN-REMOTE', 'Firstname' => 'Maria', 'Lastname' => 'Santos', 'Middlename' => 'Reyes',
            'Birthdate' => '1990-05-21', 'Gender' => 'F', 'CivilStatus' => 'single', 'Address' => 'Poblacion, Alaminos City',
            'LockStatus' => '0', 'Disability_id' => '0', 'SourceIncome_id' => '', 'Educational_id' => '', 'Ethnicity' => '',
        ];
        $gateway = Mockery::mock(BhwisGateway::class);
        $gateway->shouldReceive('findResident')->once()->andReturn($personal);
        $gateway->shouldReceive('linkedRecords')->once()->andReturn([
            'personal' => [$personal],
            'family_members' => [
                ['FamilyNumber' => 'F-100', 'Building_RegistryNumber' => 'B-1', 'PIN' => 'PIN-REMOTE'],
                ['FamilyNumber' => 'F-100', 'Building_RegistryNumber' => 'B-1', 'PIN' => 'PIN-RELATIVE'],
                ['FamilyNumber' => 'F-100', 'Building_RegistryNumber' => 'B-1', 'PIN' => 'PIN-NOT-IMPORTED'],
            ],
            'bhw_master' => [], 'barangay' => [], 'civil_status' => [],
            'source_income_type' => [], 'educational_attainment' => [],
        ]);
        $this->app->instance(BhwisGateway::class, $gateway);

        $this->postJson('/api/resident-portal/register', $this->payload('PIN-REMOTE'))
            ->assertCreated()->assertJsonPath('resident.resident_id', 'PIN-REMOTE');

        $imported = Resident::where('resident_id', 'PIN-REMOTE')->firstOrFail();
        $this->assertTrue($imported->is_legacy_imported);
        $this->assertSame($imported->household_id, $relative->fresh()->household_id);
        $this->assertDatabaseMissing('residents', ['resident_id' => 'PIN-NOT-IMPORTED']);
        $this->assertDatabaseHas('legacy_resident_links', ['source_system' => 'bhwis', 'legacy_pin' => 'PIN-REMOTE']);
    }

    private function payload(string $pin): array
    {
        $payload = [
            'resident_id' => $pin, 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => strtolower($pin).'@example.test', 'email_code' => '123456',
            'mpin' => '123456', 'mpin_confirmation' => '123456',
            'terms_accepted' => true, 'privacy_notice_acknowledged' => true, 'bhwis_import_consented' => true,
            'device_name' => 'test-device',
        ];
        $challenge = ResidentEmailVerificationCode::create([
            'challenge_id' => (string) str()->uuid(), 'resident_identifier' => $pin,
            'last_name' => $payload['last_name'], 'birth_date' => $payload['birth_date'],
            'email' => $payload['email'], 'code_hash' => Hash::make($payload['email_code']),
            'expires_at' => now()->addMinutes(10),
        ]);
        $payload['email_challenge_id'] = $challenge->challenge_id;

        return $payload;
    }
}
