<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasPermissionChecks
{
    /**
     * Check if the current user has a specific permission.
     *
     * @param string|array $permissions
     * @return bool
     */
    public function userCan($permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (is_string($permissions)) {
            return auth()->user()->hasPermissionTo($permissions);
        }

        if (is_array($permissions)) {
            return auth()->user()->hasAnyPermission($permissions);
        }

        return false;
    }

    /**
     * Check if the current user has all specified permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function userCanAll(array $permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (!auth()->user()->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the current user has a specific role.
     *
     * @param string|array $roles
     * @return bool
     */
    public function userHasRole($roles): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (is_string($roles)) {
            return auth()->user()->hasRole($roles);
        }

        if (is_array($roles)) {
            return auth()->user()->hasAnyRole($roles);
        }

        return false;
    }
}
