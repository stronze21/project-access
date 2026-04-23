<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class MiddlewareServiceProvider extends RouteServiceProvider
{
    /**
     * Register the application's middleware.
     */
    public function register(): void
    {

        // Register your custom permission middleware
        $this->app['router']->aliasMiddleware('check.permission', \App\Http\Middleware\CheckPermission::class);
    }
}
