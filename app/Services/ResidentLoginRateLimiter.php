<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ResidentLoginRateLimiter
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function key(Request $request, string $login): string
    {
        $identity = mb_strtolower(trim($login));

        return 'resident-login:'.hash('sha256', $identity.'|'.$request->ip());
    }

    public function isLimited(Request $request, string $login): bool
    {
        return RateLimiter::tooManyAttempts($this->key($request, $login), self::MAX_ATTEMPTS);
    }

    public function hit(Request $request, string $login): void
    {
        RateLimiter::hit($this->key($request, $login), self::DECAY_SECONDS);
    }

    public function clear(Request $request, string $login): void
    {
        RateLimiter::clear($this->key($request, $login));
    }

    public function retryAfter(Request $request, string $login): int
    {
        return max(1, RateLimiter::availableIn($this->key($request, $login)));
    }
}
