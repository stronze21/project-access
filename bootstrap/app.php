<?php

use App\Exceptions\BhwisUnavailableException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureResidentPortalMobileDevice;
use App\Http\Middleware\EnsureResidentPortalSessionFresh;
use App\Http\Middleware\EnsureIdempotentRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )->withProviders(require __DIR__.'/providers.php')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('resident-portal') || $request->is('resident-portal/*')
                ? route('resident-portal.login')
                : route('login')
        );
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('resident-portal') || $request->is('resident-portal/*')
                ? route('resident-portal.home')
                : route('dashboard')
        );

        // Add our custom permission middleware
        $middleware->alias([
            'check.permission' => CheckPermission::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'module.enabled' => EnsureModuleEnabled::class,
            'resident.mobile' => EnsureResidentPortalMobileDevice::class,
            'resident.session' => EnsureResidentPortalSessionFresh::class,
            'idempotent' => EnsureIdempotentRequest::class,
        ]);

        // Define the admin middleware group
        $middleware->group('admin', [
            'web',
            'auth',
            'verified',
            'permission:manage-users',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (BhwisUnavailableException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The BHWIS local server is temporarily unavailable. Please try again later.',
                    'error' => 'bhwis_unavailable',
                    'retryable' => true,
                ], 503, ['Retry-After' => '60']);
            }

            return response()
                ->view('errors.bhwis-unavailable', [
                    'retryUrl' => $request->isMethod('GET') ? $request->fullUrl() : null,
                ], 503)
                ->header('Retry-After', '60');
        });
    })->create();
