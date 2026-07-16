<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResidentPortalSessionFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $expiresAt = $request->session()->get('resident_portal_expires_at');

        if ($expiresAt && now()->greaterThanOrEqualTo($expiresAt)) {
            Auth::guard('resident')->logout();
            $request->session()->forget(['resident_portal_expires_at', 'resident_portal_authenticated_at']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('resident-portal.login')
                ->withErrors(['login' => 'Your resident portal session has expired. Please sign in again.']);
        }

        return $next($request);
    }
}
