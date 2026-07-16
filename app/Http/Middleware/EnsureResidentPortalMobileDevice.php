<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResidentPortalMobileDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = (string) $request->userAgent();
        $isIos = preg_match('/iPhone|iPad|iPod/i', $userAgent) === 1;
        $isIPadOs = preg_match('/Macintosh.*Mobile/i', $userAgent) === 1;
        $isAndroid = preg_match('/Android/i', $userAgent) === 1;

        abort_unless(
            $isIos || $isIPadOs || $isAndroid,
            Response::HTTP_FORBIDDEN,
            'The Resident Portal web app is available only on supported mobile devices.'
        );

        return $next($request);
    }
}
