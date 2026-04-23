<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@ayudahub.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create regular staff users
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@ayudahub.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Distribution Officer',
            'email' => 'distribution@ayudahub.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Verification Officer',
            'email' => 'verification@ayudahub.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}