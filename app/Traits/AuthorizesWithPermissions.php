<?php

namespace App\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

trait AuthorizesWithPermissions
{
    /**
     * Authorize a given action against a set of permissions.
     *
     * @param  string|array  $permissions
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizePermission($permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        if (! Auth::user() || ! Auth::user()->hasAnyPermission($permissions)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    /**
     * Authorize a given action against a set of roles.
     *
     * @param  string|array  $roles
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeRole($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];

        if (! Auth::user() || ! Auth::user()->hasAnyRole($roles)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    /**
     * Determine if the current user has any of the given permissions.
     *
     * @param  string|array  $permissions
     * @return bool
     */
    public function hasPermission($permissions)
    {
        return Auth::user() && Auth::user()->hasAnyPermission($permissions);
    }

    /**
     * Determine if the current user has all of the given permissions.
     *
     * @param  array  $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions)
    {
        if (! Auth::user()) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (! Auth::user()->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the current user has any of the given roles.
     *
     * @param  string|array  $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        return Auth::user() && Auth::user()->hasAnyRole($roles);
    }
}
