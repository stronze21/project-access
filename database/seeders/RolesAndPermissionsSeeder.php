<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        // System/Users management permissions
        $systemPermissions = [
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'configure-system',
            'view-audit-logs',
            'export-all-data',
        ];

        // Residents and Households permissions
        $residentPermissions = [
            'view-residents',
            'create-residents',
            'edit-residents',
            'delete-residents',
            'view-households',
            'create-households',
            'edit-households',
            'delete-households',
            'generate-qr-codes',
            'manage-rfid',
        ];

        // Aid Program permissions
        $programPermissions = [
            'view-programs',
            'create-programs',
            'edit-programs',
            'delete-programs',
            'approve-programs',
            'manage-eligibility-criteria',
        ];

        // Distribution permissions
        $distributionPermissions = [
            'view-distributions',
            'create-distributions',
            'approve-distributions',
            'manage-distribution-batches',
            'verify-beneficiaries',
            'process-aid-transfers',
        ];

        // Report permissions
        $reportPermissions = [
            'view-reports',
            'export-reports',
            'create-custom-reports',
            'view-program-statistics',
            'view-beneficiary-statistics',
            'view-distribution-statistics',
        ];

        // Resident Portal management permissions (admin-side management)
        $residentPortalPermissions = [
            'manage-announcements',
            'manage-citizen-services',
        ];

        $bosesmotoPermissions = [
            'manage-complaints',
            'view-complaint-audit-logs',
            'manage-complaint-reference-data',
            'manage-polls',
            'moderate-sentiments',
        ];

        // Merge all permissions and create them
        $allPermissions = array_merge(
            $systemPermissions,
            $residentPermissions,
            $programPermissions,
            $distributionPermissions,
            $reportPermissions,
            $residentPortalPermissions,
            $bosesmotoPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. System Administrator - Has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'system-administrator']);
        $adminRole->givePermissionTo(Permission::all());

        $bosesmotoAdminRole = Role::firstOrCreate(['name' => 'Admin']);
        $bosesmotoAdminRole->givePermissionTo($bosesmotoPermissions);

        $bosesmotoSuperAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $bosesmotoSuperAdminRole->givePermissionTo(Permission::all());

        $mayorRole = Role::firstOrCreate(['name' => 'mayor']);
        $mayorRole->givePermissionTo(['manage-complaints', 'view-complaint-audit-logs', 'manage-polls', 'moderate-sentiments']);

        $departmentHeadRole = Role::firstOrCreate(['name' => 'department-head']);
        $departmentHeadRole->givePermissionTo(['manage-complaints']);

        $actionOfficerRole = Role::firstOrCreate(['name' => 'action-officer']);
        $actionOfficerRole->givePermissionTo(['manage-complaints']);

        Role::firstOrCreate(['name' => 'citizen']);

        // 2. Program Manager
        $managerRole = Role::firstOrCreate(['name' => 'program-manager']);
        $managerRole->givePermissionTo([
            // System permissions
            'view-users',

            // Resident/Household permissions
            'view-residents',
            'view-households',

            // Program permissions
            'view-programs',
            'create-programs',
            'edit-programs',
            'delete-programs',
            'approve-programs',
            'manage-eligibility-criteria',

            // Distribution permissions
            'view-distributions',
            'approve-distributions',

            // Report permissions
            'view-reports',
            'export-reports',
            'view-program-statistics',
            'view-beneficiary-statistics',
            'view-distribution-statistics',

            // Resident Portal management
            'manage-announcements',
            'manage-citizen-services',
        ]);

        // 3. Registration Officer
        $registrationRole = Role::firstOrCreate(['name' => 'registration-officer']);
        $registrationRole->givePermissionTo([
            // Resident/Household permissions
            'view-residents',
            'create-residents',
            'edit-residents',
            'view-households',
            'create-households',
            'edit-households',
            'generate-qr-codes',
            'manage-rfid',

            // Limited Distribution view
            'view-distributions',

            // Report permissions
            'view-reports',
            'view-beneficiary-statistics',

        ]);

        // 4. Distribution Officer
        $distributionRole = Role::firstOrCreate(['name' => 'distribution-officer']);
        $distributionRole->givePermissionTo([
            // Limited Resident/Household permissions
            'view-residents',
            'view-households',

            // Limited program permissions
            'view-programs',

            // Distribution permissions
            'view-distributions',
            'create-distributions',
            'manage-distribution-batches',
            'verify-beneficiaries',
            'process-aid-transfers',

            // Report permissions
            'view-reports',
            'view-distribution-statistics',
        ]);

        // 5. Reporting User
        $reportingRole = Role::firstOrCreate(['name' => 'reporting-user']);
        $reportingRole->givePermissionTo([
            // View only permissions
            'view-residents',
            'view-households',
            'view-programs',
            'view-distributions',
            'view-reports',
            'export-reports',
            'view-program-statistics',
            'view-beneficiary-statistics',
            'view-distribution-statistics',
        ]);
    }
}
