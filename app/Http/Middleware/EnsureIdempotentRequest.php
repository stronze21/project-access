<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $key = trim((string) $request->header('X-Idempotency-Key'));
        if ($key === '') {
            return $next($request);
        }

        if (strlen($key) > 100 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $key)) {
            return response()->json(['message' => 'The idempotency key is invalid.'], 422);
        }

        $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();
        $cacheKey = 'idempotency:'.hash('sha256', $identity.'|'.$request->method().'|'.$request->route()?->uri().'|'.$key);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json($cached['body'], $cached['status'], ['X-Idempotent-Replayed' => 'true']);
        }

        $lock = Cache::lock($cacheKey.':lock', 15);
        if (! $lock->get()) {
            return response()->json(['message' => 'This request is already being processed.'], 409, ['Retry-After' => '2']);
        }

        try {
            $response = $next($request);
            if ($response instanceof JsonResponse && $response->isSuccessful()) {
                Cache::put($cacheKey, [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getData(true),
                ], now()->addDay());
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
