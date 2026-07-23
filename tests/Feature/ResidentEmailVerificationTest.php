<?php

namespace Tests\Feature;

use App\Mail\ResidentEmailVerificationCodeMail;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResidentEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_activation_requires_and_consumes_an_emailed_confirmation_code(): void
    {
        Mail::fake();
        Resident::create([
            'resident_id' => 'PIN-EMAIL', 'first_name' => 'Maria', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);

        $challengeResponse = $this->postJson('/api/resident-portal/register/email-code', [
            'resident_id' => 'PIN-EMAIL', 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => 'maria@example.test',
        ])->assertOk();

        $code = null;
        Mail::assertSent(ResidentEmailVerificationCodeMail::class, function ($mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->hasTo('maria@example.test');
        });

        $this->postJson('/api/resident-portal/register', [
            'resident_id' => 'PIN-EMAIL', 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => 'maria@example.test', 'email_challenge_id' => $challengeResponse->json('challenge_id'),
            'email_code' => $code, 'mpin' => '123456', 'mpin_confirmation' => '123456',
            'terms_accepted' => true, 'privacy_notice_acknowledged' => true, 'bhwis_import_consented' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('residents', ['resident_id' => 'PIN-EMAIL', 'email' => 'maria@example.test']);
        $this->assertDatabaseHas('resident_email_verification_codes', ['challenge_id' => $challengeResponse->json('challenge_id')]);
        $this->assertNotNull(\App\Models\ResidentEmailVerificationCode::where('challenge_id', $challengeResponse->json('challenge_id'))->value('consumed_at'));
    }

    public function test_wrong_code_does_not_activate_the_account(): void
    {
        Mail::fake();
        $resident = Resident::create([
            'resident_id' => 'PIN-WRONG', 'first_name' => 'Ana', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);
        $challenge = $this->postJson('/api/resident-portal/register/email-code', [
            'resident_id' => 'PIN-WRONG', 'last_name' => 'Santos', 'birth_date' => '1990-05-21', 'email' => 'ana@example.test',
        ])->json('challenge_id');

        $this->postJson('/api/resident-portal/register', [
            'resident_id' => 'PIN-WRONG', 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => 'ana@example.test', 'email_challenge_id' => $challenge, 'email_code' => '000000',
            'mpin' => '123456', 'mpin_confirmation' => '123456', 'terms_accepted' => true,
            'privacy_notice_acknowledged' => true, 'bhwis_import_consented' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('email_code');

        $this->assertNull($resident->fresh()->mpin);
    }

    public function test_pwa_account_creation_requires_the_emailed_code(): void
    {
        Mail::fake();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 15) AppleWebKit/537.36 Chrome Mobile Safari/537.36');
        $resident = Resident::create([
            'resident_id' => 'PIN-PWA', 'first_name' => 'Lina', 'last_name' => 'Santos',
            'birth_date' => '1990-05-21', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);

        $this->post('/resident-portal/register/email-code', [
            'resident_id' => 'PIN-PWA', 'last_name' => 'Santos', 'birth_date' => '1990-05-21', 'email' => 'lina@example.test',
        ])->assertRedirect();

        $challenge = \App\Models\ResidentEmailVerificationCode::where('resident_identifier', 'PIN-PWA')->firstOrFail();
        $code = null;
        Mail::assertSent(ResidentEmailVerificationCodeMail::class, function ($mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->post('/resident-portal/register', [
            'resident_id' => 'PIN-PWA', 'last_name' => 'Santos', 'birth_date' => '1990-05-21',
            'email' => 'lina@example.test', 'email_challenge_id' => $challenge->challenge_id, 'email_code' => $code,
            'mpin' => '123456', 'mpin_confirmation' => '123456', 'terms_accepted' => '1',
            'privacy_notice_acknowledged' => '1', 'bhwis_import_consented' => '1',
        ])->assertRedirect(route('resident-portal.home'));

        $this->assertSame('lina@example.test', $resident->fresh()->email);
        $this->assertAuthenticatedAs($resident->fresh(), 'resident');
    }
}
