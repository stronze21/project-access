<?php

namespace Database\Seeders;

use App\Models\SosDepartment;
use Illuminate\Database\Seeder;

class SosDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Ambulance',
                'code' => 'ambulance',
                'description' => 'Medical response and patient transport',
                'hotline' => '911',
                'sort_order' => 10,
            ],
            [
                'name' => 'Police',
                'code' => 'police',
                'description' => 'Law enforcement and public safety response',
                'hotline' => '911',
                'sort_order' => 20,
            ],
            [
                'name' => 'Rescue',
                'code' => 'rescue',
                'description' => 'Search, rescue, disaster, and urgent field response',
                'hotline' => '911',
                'sort_order' => 30,
            ],
            [
                'name' => 'Fire Department',
                'code' => 'fire',
                'description' => 'Fire suppression and related emergency response',
                'hotline' => '911',
                'sort_order' => 40,
            ],
        ];

        foreach ($departments as $department) {
            SosDepartment::query()->updateOrCreate(
                ['code' => $department['code']],
                array_merge($department, ['is_active' => true])
            );
        }
    }
}
