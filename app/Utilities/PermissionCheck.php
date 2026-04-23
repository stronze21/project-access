<?php

namespace App\Utilities;

use Illuminate\Support\Facades\Auth;

class PermissionCheck
{
    /**
     * Check if the current user has a specific permission.
     *
     * @param string|array $permissions
     * @return bool
     */
    public static function can($permissions): bool
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
    public static function canAll(array $permissions): bool
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
    public static function hasRole($roles): bool
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
