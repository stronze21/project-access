<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives
        $this->registerBladeDirectives();

        // Register permissions as Gates
        $this->registerPermissionsAsGates();
    }

    /**
     * Register permissions as Gates.
     * This allows using `$user->can('permission-name')` and `@can('permission-name')` in Blade
     */
    protected function registerPermissionsAsGates(): void
    {
        try {
            // Only register permissions if the permissions table exists and has been migrated
            if (app()->environment() !== 'testing' && \Schema::hasTable('permissions')) {
                $permissions = Permission::all();

                foreach ($permissions as $permission) {
                    Gate::define($permission->name, function ($user) use ($permission) {
                        return $user->hasPermissionTo($permission);
                    });
                }

                // Define a gate for superadmins to bypass all permission checks
                Gate::before(function ($user, $ability) {
                    if ($user->hasRole('system-administrator')) {
                        return true;
                    }
                });
            }
        } catch (\Exception $e) {
            // If the permissions table doesn't exist yet (e.g., during migrations),
            // simply skip registration without error
        }
    }

    /**
     * Register custom Blade directives for permission checks
     */
    protected function registerBladeDirectives(): void
    {
        // @role directive
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });
        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });

        // @elserole directive for if-else role chains
        Blade::directive('elserole', function ($role) {
            return "<?php elseif(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        // @permission directive
        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->hasPermissionTo({$permission})): ?>";
        });
        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        // @elsepermission directive for if-else permission chains
        Blade::directive('elsepermission', function ($permission) {
            return "<?php elseif(auth()->check() && auth()->user()->hasPermissionTo({$permission})): ?>";
        });

        // @anypermission directive
        Blade::directive('anypermission', function ($permissions) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyPermission({$permissions})): ?>";
        });
        Blade::directive('endanypermission', function () {
            return "<?php endif; ?>";
        });

        // @anyrole directive
        Blade::directive('anyrole', function ($roles) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyRole({$roles})): ?>";
        });
        Blade::directive('endanyrole', function () {
            return "<?php endif; ?>";
        });

        // @roleOrPermission directive
        Blade::directive('roleOrPermission', function ($roleOrPermission) {
            list($role, $permission) = explode(',', $roleOrPermission);
            return
                "<?php if(auth()->check() && (auth()->user()->hasRole({$role}) || auth()->user()->hasPermissionTo({$permission}))): ?>";
        });
        Blade::directive('endroleOrPermission', function () {
            return "<?php endif; ?>";
        });

        // @roleIs directive for comparing current user's role with a string
        Blade::directive('roleIs', function ($variable) {
            return "<?php if(auth()->check() && auth()->user()->roles->pluck('name')->first() == {$variable}): ?>";
        });
        Blade::directive('endroleIs', function () {
            return "<?php endif; ?>";
        });

        // @notRole directive to check if user does not have a role
        Blade::directive('notRole', function ($role) {
            return "<?php if(auth()->check() && !auth()->user()->hasRole({$role})): ?>";
        });
        Blade::directive('endnotRole', function () {
            return "<?php endif; ?>";
        });

        // @notPermission directive to check if user does not have a permission
        Blade::directive('notPermission', function ($permission) {
            return "<?php if(auth()->check() && !auth()->user()->hasPermissionTo({$permission})): ?>";
        });
        Blade::directive('endnotPermission', function () {
            return "<?php endif; ?>";
        });
    }
}
