<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:assign-role
                            {email : The email of the user}
                            {role : The role to assign}
                            {--remove : Remove the role instead of assigning it}
                            {--P|permissions=* : Direct permissions to assign/remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign or remove a role and/or permissions to/from a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');
        $isRemove = $this->option('remove');
        $permissions = $this->option('permissions');

        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Check if role exists
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Role {$roleName} does not exist.");
            return 1;
        }

        // Assign or remove role
        if ($isRemove) {
            $user->removeRole($role);
            $this->info("Role {$roleName} removed from user {$email}.");
        } else {
            $user->assignRole($role);
            $this->info("Role {$roleName} assigned to user {$email}.");
        }

        // Handle direct permissions
        if (!empty($permissions)) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();

                if (!$permission) {
                    $this->warn("Permission {$permissionName} does not exist.");
                    continue;
                }

                if ($isRemove) {
                    $user->revokePermissionTo($permission);
                    $this->info("Permission {$permissionName} removed from user {$email}.");
                } else {
                    $user->givePermissionTo($permission);
                    $this->info("Permission {$permissionName} assigned to user {$email}.");
                }
            }
        }

        return 0;
    }
}
