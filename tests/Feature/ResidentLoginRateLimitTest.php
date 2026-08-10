<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Services\ResidentLoginRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ResidentLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_pwa_and_mobile_share_a_five_failure_login_limit(): void
    {
        $resident = $this->resident('26-90001');
        $limiter = app(ResidentLoginRateLimiter::class);
        $request = request()->duplicate(server: ['REMOTE_ADDR' => '127.0.0.1']);
        RateLimiter::clear($limiter->key($request, $resident->resident_id));

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/resident-portal/login', [
                'login' => $resident->resident_id,
                'mpin' => '999999',
            ])->assertUnauthorized();
        }

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')
                ->post('/resident-portal/login', [
                    'login' => $resident->resident_id,
                    'mpin' => '999999',
                ])->assertRedirect();
        }

        $this->postJson('/api/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '999999',
        ])->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', 'Too many login attempts. Please wait before trying again.');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Mobile')
            ->post('/resident-portal/login', [
                'login' => $resident->resident_id,
                'mpin' => '999999',
            ])->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertSessionHasErrors('login');
    }

    public function test_successful_mobile_login_clears_previous_failures(): void
    {
        $resident = $this->resident('26-90002');

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->postJson('/api/resident-portal/login', [
                'login' => $resident->resident_id,
                'mpin' => '999999',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '123456',
        ])->assertOk();

        $this->postJson('/api/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '999999',
        ])->assertUnauthorized();
    }

    private function resident(string $pin): Resident
    {
        return Resident::create([
            'resident_id' => $pin,
            'first_name' => 'Rate',
            'last_name' => 'Limited',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
            'mpin' => Hash::make('123456'),
        ]);
    }
}
