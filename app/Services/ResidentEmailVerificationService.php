<?php

namespace App\Services;

use App\Mail\ResidentEmailVerificationCodeMail;
use App\Models\ResidentEmailVerificationCode;
use App\Services\Bhwis\ResidentActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResidentEmailVerificationService
{
    public function __construct(
        private ResidentActivationService $activation,
        private DynamicMailConfig $mailConfig,
    ) {}

    public function send(array $data, Request $request): ResidentEmailVerificationCode
    {
        $this->activation->assertCanActivate($data);
        $key = 'resident-email-code:'.hash('sha256', strtolower($data['resident_id'].'|'.$data['email'].'|'.$request->ip()));
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['email' => 'Too many confirmation codes requested. Please wait before trying again.']);
        }
        RateLimiter::hit($key, 300);

        ResidentEmailVerificationCode::where('resident_identifier', trim($data['resident_id']))
            ->whereNull('consumed_at')->delete();

        $code = (string) random_int(100000, 999999);
        $challenge = ResidentEmailVerificationCode::create([
            'challenge_id' => (string) Str::uuid(),
            'resident_identifier' => trim($data['resident_id']),
            'last_name' => trim($data['last_name']),
            'birth_date' => $data['birth_date'],
            'email' => strtolower(trim($data['email'])),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->mailConfig->apply();
        Mail::to($challenge->email)->send(new ResidentEmailVerificationCodeMail($code));

        return $challenge;
    }

    public function verify(array $data): ResidentEmailVerificationCode
    {
        $challenge = ResidentEmailVerificationCode::where('challenge_id', $data['email_challenge_id'])->first();
        $validIdentity = $challenge
            && hash_equals($challenge->resident_identifier, trim($data['resident_id']))
            && hash_equals(mb_strtolower($challenge->last_name), mb_strtolower(trim($data['last_name'])))
            && $challenge->birth_date->toDateString() === date('Y-m-d', strtotime($data['birth_date']))
            && hash_equals($challenge->email, strtolower(trim($data['email'])));

        if (! $validIdentity || $challenge->consumed_at || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
            throw ValidationException::withMessages(['email_code' => 'The confirmation code is invalid or expired. Request a new code.']);
        }

        if (! Hash::check((string) $data['email_code'], $challenge->code_hash)) {
            $challenge->increment('attempts');
            throw ValidationException::withMessages(['email_code' => 'The confirmation code is incorrect.']);
        }

        $challenge->update(['verified_at' => now()]);

        return $challenge;
    }

    public function consume(ResidentEmailVerificationCode $challenge): void
    {
        $challenge->update(['consumed_at' => now()]);
    }
}
