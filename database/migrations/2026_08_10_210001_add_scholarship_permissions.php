<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $manage = Permission::firstOrCreate(['name' => 'manage-scholarship-applications', 'guard_name' => 'web']);
        $view = Permission::firstOrCreate(['name' => 'view-scholarship-applications', 'guard_name' => 'web']);

        Role::query()
            ->whereIn('name', ['system-administrator', 'Super Admin', 'program-manager'])
            ->get()
            ->each(function (Role $role) use ($manage, $view): void {
                $role->givePermissionTo([$manage, $view]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', ['manage-scholarship-applications', 'view-scholarship-applications'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
