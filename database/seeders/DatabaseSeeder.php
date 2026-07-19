<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $this->call(UserSeeder::class);

        $this->call([
            RegionSeeder::class,
            ProvinceSeeder::class,
            CityMunicipalitySeeder::class,
            BarangaySeeder::class,
        ]);

        // Create locations
        $this->call(LocationsSeeder::class);
        $this->call(SystemSettingsSeeder::class);

        // Citizen service portal data
        $this->call([
            PublicServiceLinkSeeder::class,
            SosDepartmentSeeder::class,
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultAdminSeeder::class,
            FeedbackReferenceSeeder::class,
            UserThreeCitizenServicesSeeder::class,
            // Add other seeders here
        ]);

        // Resident export/import permissions
        Permission::firstOrCreate(['name' => 'export-residents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'import-residents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-legacy-reference-data', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'system-administrator', 'guard_name' => 'web']);
        // Add these permissions to your admin role
        $adminRole->givePermissionTo('export-residents');
        $adminRole->givePermissionTo('import-residents');
        $adminRole->givePermissionTo('manage-legacy-reference-data');
    }
}
