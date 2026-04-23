<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (Auth::guest()) {
            return redirect()->route('login');
        }

        if (! Auth::user()->hasPermissionTo($permission)) {
            abort(403, 'Unauthorized action. You do not have the necessary permissions.');
        }

        return $next($request);
    }
}
