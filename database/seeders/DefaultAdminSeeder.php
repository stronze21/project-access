<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default admin user
        $admin = User::updateOrCreate(
            ['email' => 'joshua070915@gmail.com'],
            [
                'name' => 'Joshua',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign the admin role
        $adminRole = Role::where('name', 'system-administrator')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        // Create sample users for each role
        $roles = [
            'program-manager' => 'Program Manager',
            'registration-officer' => 'Registration Officer',
            'distribution-officer' => 'Distribution Officer',
            'reporting-user' => 'Reporting User',
        ];

        foreach ($roles as $roleName => $userName) {
            $user = User::firstOrCreate(
                ['email' => $roleName . '@example.com'],
                [
                    'name' => $userName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
