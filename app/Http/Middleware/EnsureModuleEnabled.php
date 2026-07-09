<?php

namespace App\Http\Middleware;

use App\Services\ModuleSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleSettings $modules)
    {
    }

    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        foreach ($modules as $module) {
            if (! $this->modules->enabled($module)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'This module is currently unavailable.',
                    ], 404);
                }

                abort(404);
            }
        }

        return $next($request);
    }
}
