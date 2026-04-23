<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\CheckPermission;
use Spatie\Permission\Middleware\RoleMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )->withProviders(require __DIR__.'/providers.php')
    ->withMiddleware(function (Middleware $middleware) {
        // Add our custom permission middleware
        $middleware->alias([
            'check.permission' => CheckPermission::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
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
        //
    })->create();
