<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\RegionSeeder;
use Spatie\Permission\Models\Role;
use Database\Seeders\BarangaySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ResidentSeeder;
use Database\Seeders\SosAlertSeeder;
use Database\Seeders\HouseholdSeeder;
use Database\Seeders\LocationsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\AyudaProgramSeeder;
use Database\Seeders\DefaultAdminSeeder;
use Database\Seeders\DistributionSeeder;
use Database\Seeders\GrievanceReportSeeder;
use Spatie\Permission\Models\Permission;
use Database\Seeders\CityMunicipalitySeeder;
use Database\Seeders\EmergencyAlertSeeder;
use Database\Seeders\DistributionBatchSeeder;
use Database\Seeders\EligibilityCriteriaSeeder;
use Database\Seeders\PublicServiceLinkSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\CitizenServiceRequestSeeder;

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

        // Create residents and households
        $this->call(HouseholdSeeder::class);
        $this->call(ResidentSeeder::class);

        // Create ayuda programs and eligibility criteria
        $this->call(AyudaProgramSeeder::class);
        $this->call(EligibilityCriteriaSeeder::class);

        // Create distributions and batches
        $this->call(DistributionBatchSeeder::class);
        $this->call(DistributionSeeder::class);

        // Citizen service portal data
        $this->call([
            PublicServiceLinkSeeder::class,
            CitizenServiceRequestSeeder::class,
            GrievanceReportSeeder::class,
            EmergencyAlertSeeder::class,
            SosAlertSeeder::class,
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultAdminSeeder::class,
            // Add other seeders here
        ]);


        // Resident export/import permissions
        Permission::create(['name' => 'export-residents', 'guard_name' => 'web']);
        Permission::create(['name' => 'import-residents', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'system-administrator', 'guard_name' => 'web']);
        // Add these permissions to your admin role
        $adminRole->givePermissionTo('export-residents');
        $adminRole->givePermissionTo('import-residents');
    }
}
