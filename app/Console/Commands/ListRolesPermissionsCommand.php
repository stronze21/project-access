<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ListRolesPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:list-roles-permissions
                            {--role= : Show permissions for a specific role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all roles and permissions in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificRole = $this->option('role');

        if ($specificRole) {
            $this->listSpecificRole($specificRole);
        } else {
            $this->listAllRolesAndPermissions();
        }

        return 0;
    }

    /**
     * List all roles and permissions.
     */
    private function listAllRolesAndPermissions()
    {
        $this->info('Available roles:');
        $roles = Role::all();

        $roleData = [];
        foreach ($roles as $role) {
            $roleData[] = [
                'name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'users_count' => $role->users->count(),
            ];
        }

        $this->table(['Role Name', 'Permissions Count', 'Users Count'], $roleData);

        $this->newLine();
        $this->info('Available permissions:');
        $permissions = Permission::all();

        $permissionGroups = [];
        foreach ($permissions as $permission) {
            $parts = explode('-', $permission->name);
            $group = isset($parts[0]) ? $parts[0] : 'other';

            if (!isset($permissionGroups[$group])) {
                $permissionGroups[$group] = [];
            }

            $permissionGroups[$group][] = $permission->name;
        }

        foreach ($permissionGroups as $group => $groupPermissions) {
            $this->comment(strtoupper($group) . ' Permissions:');
            sort($groupPermissions);
            $this->info('  - ' . implode("\n  - ", $groupPermissions));
            $this->newLine();
        }
    }

    /**
     * List a specific role and its permissions.
     */
    private function listSpecificRole($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Role {$roleName} does not exist.");
            return;
        }

        $this->info("Details for role: {$role->name}");
        $this->newLine();

        $this->info('Permissions:');
        $permissions = $role->permissions;

        if ($permissions->count() === 0) {
            $this->warn('  This role has no permissions.');
        } else {
            $permissionGroups = [];
            foreach ($permissions as $permission) {
                $parts = explode('-', $permission->name);
                $group = isset($parts[0]) ? $parts[0] : 'other';

                if (!isset($permissionGroups[$group])) {
                    $permissionGroups[$group] = [];
                }

                $permissionGroups[$group][] = $permission->name;
            }

            foreach ($permissionGroups as $group => $groupPermissions) {
                $this->comment(strtoupper($group) . ':');
                sort($groupPermissions);
                $this->info('  - ' . implode("\n  - ", $groupPermissions));
                $this->newLine();
            }
        }

        $this->info('Users with this role:');
        $users = $role->users;

        if ($users->count() === 0) {
            $this->warn('  No users have this role assigned.');
        } else {
            $userData = [];
            foreach ($users as $user) {
                $userData[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'direct_permissions' => $user->getDirectPermissions()->count(),
                ];
            }

            $this->table(['ID', 'Name', 'Email', 'Direct Permissions'], $userData);
        }
    }
}
