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
        User::firstOrCreate(
            ['email' => 'joshua070915@gmail.com'],
            [
                'name' => 'Joshua',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create regular staff users
        User::firstOrCreate(
            ['email' => 'staff@ayudahub.test'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'distribution@ayudahub.test'],
            [
                'name' => 'Distribution Officer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'verification@ayudahub.test'],
            [
                'name' => 'Verification Officer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
