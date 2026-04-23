<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Validator::extend('philippine_phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^09\d{9}$/', $value) ||
                   preg_match('/^\+639\d{9}$/', $value) ||
                   preg_match('/^639\d{9}$/', $value) ||
                   preg_match('/^0[1-8]\d{9}$/', $value);
        });
        // Permission directive
        Blade::directive('permission', function ($expression) {
            return "<?php if(auth()->check() && auth()->user()->hasPermissionTo({$expression})): ?>";
});

Blade::directive('endpermission', function () {
return "<?php endif; ?>";
});

// Role directive
Blade::directive('role', function ($expression) {
return "<?php if(auth()->check() && auth()->user()->hasRole({$expression})): ?>";
});

Blade::directive('endrole', function () {
return "<?php endif; ?>";
});

// Has any permission directive
Blade::directive('anypermission', function ($expression) {
return "<?php if(auth()->check() && auth()->user()->hasAnyPermission({$expression})): ?>";
});

Blade::directive('endanypermission', function () {
return "<?php endif; ?>";
});

// Has all permissions directive
Blade::directive('allpermissions', function ($expression) {
return "<?php if(auth()->check() && auth()->user()->hasAllPermissions({$expression})): ?>";
});

Blade::directive('endallpermissions', function () {
return "<?php endif; ?>";
});
}
}