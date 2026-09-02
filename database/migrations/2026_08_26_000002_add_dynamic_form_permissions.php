<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $manage = Permission::firstOrCreate(['name' => 'manage-forms', 'guard_name' => 'web']);
        $fill = Permission::firstOrCreate(['name' => 'fill-forms', 'guard_name' => 'web']);
        $process = Permission::firstOrCreate(['name' => 'process-forms', 'guard_name' => 'web']);
        $view = Permission::firstOrCreate(['name' => 'view-forms', 'guard_name' => 'web']);

        Role::query()
            ->whereIn('name', ['system-administrator', 'Super Admin', 'program-manager'])
            ->get()
            ->each(function (Role $role) use ($manage, $fill, $process, $view): void {
                $role->givePermissionTo([$manage, $fill, $process, $view]);
            });

        Role::query()
            ->where('name', 'registration-officer')
            ->get()
            ->each(function (Role $role) use ($fill, $view): void {
                $role->givePermissionTo([$fill, $view]);
            });

        Role::query()
            ->where('name', 'reporting-user')
            ->get()
            ->each(function (Role $role) use ($view): void {
                $role->givePermissionTo([$view]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', ['manage-forms', 'fill-forms', 'process-forms', 'view-forms'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
