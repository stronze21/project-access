<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;

trait ComponentAuthorization
{
    /**
     * Checks if the current user has the required permission
     *
     * @param string $permission The permission to check
     * @param bool $throwException Whether to throw an exception if the check fails
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizePermission(string $permission): bool
    {
        if (!Auth::user()->hasPermissionTo($permission)) {
            // For Livewire, just set a flag and handle redirect in render()
            $this->shouldRedirect = true;
            $this->redirectRoute = 'dashboard';
            $this->redirectMessage = 'You do not have permission to access this page.';
            return false;
        }
        return true;
    }

    /**
     * Checks if the current user has the required role
     *
     * @param string|array $roles The role(s) to check
     * @param bool $throwException Whether to throw an exception if the check fails
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizeRole($roles, bool $throwException = true): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        if (Auth::user()->hasAnyRole($roles)) {
            return true;
        }

        if ($throwException) {
            throw new AuthorizationException('You do not have the necessary role to access this resource.');
        }

        return false;
    }

    /**
     * Checks if the current user has the required permission or role
     *
     * @param string|array $permissions The permission(s) to check
     * @param string|array|null $roles The role(s) to check
     * @param bool $throwException Whether to throw an exception if the check fails
     * @return bool
     * @throws AuthorizationException
     */
    public function authorizePermissionOrRole($permissions, $roles = null, bool $throwException = true): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $hasPermission = collect($permissions)->contains(fn($permission) => Auth::user()->hasPermissionTo($permission));

        if ($hasPermission) {
            return true;
        }

        if ($roles !== null) {
            $roles = is_array($roles) ? $roles : [$roles];
            if (Auth::user()->hasAnyRole($roles)) {
                return true;
            }
        }

        if ($throwException) {
            throw new AuthorizationException('You do not have the necessary permissions or roles to access this resource.');
        }

        return false;
    }
}
