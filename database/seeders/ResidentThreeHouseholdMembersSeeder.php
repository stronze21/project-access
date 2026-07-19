<?php

namespace Database\Seeders;

use App\Models\Resident;
use Illuminate\Database\Seeder;

class ResidentThreeHouseholdMembersSeeder extends Seeder
{
    public function run(): void
    {
        $resident = Resident::query()->with('household')->find(3);

        if (! $resident?->household) {
            $this->command?->warn('Resident ID 3 or their household was not found; household members were not seeded.');

            return;
        }

        $members = [
            [
                'resident_id' => 'R-RESIDENT3-MEMBER-01',
                'first_name' => 'Elena',
                'middle_name' => 'Santos',
                'birth_date' => '1988-04-12',
                'gender' => 'female',
                'civil_status' => 'married',
                'relationship_to_head' => 'spouse',
                'occupation' => 'vendor',
                'monthly_income' => 12000,
                'educational_attainment' => 'high_school',
                'is_registered_voter' => true,
            ],
            [
                'resident_id' => 'R-RESIDENT3-MEMBER-02',
                'first_name' => 'Miguel',
                'middle_name' => 'Santos',
                'birth_date' => '2008-09-18',
                'gender' => 'male',
                'civil_status' => 'single',
                'relationship_to_head' => 'child',
                'occupation' => 'student',
                'monthly_income' => 0,
                'educational_attainment' => 'high_school',
                'is_registered_voter' => false,
                'is_scholar' => true,
            ],
            [
                'resident_id' => 'R-RESIDENT3-MEMBER-03',
                'first_name' => 'Sofia',
                'middle_name' => 'Santos',
                'birth_date' => '2012-02-23',
                'gender' => 'female',
                'civil_status' => 'single',
                'relationship_to_head' => 'child',
                'occupation' => 'student',
                'monthly_income' => 0,
                'educational_attainment' => 'elementary',
                'is_registered_voter' => false,
            ],
            [
                'resident_id' => 'R-RESIDENT3-MEMBER-04',
                'first_name' => 'Roberto',
                'middle_name' => 'Cruz',
                'birth_date' => '1955-07-06',
                'gender' => 'male',
                'civil_status' => 'widowed',
                'relationship_to_head' => 'parent',
                'occupation' => 'retired',
                'monthly_income' => 3000,
                'educational_attainment' => 'high_school',
                'is_registered_voter' => true,
                'is_senior_citizen' => true,
            ],
            [
                'resident_id' => 'R-RESIDENT3-MEMBER-05',
                'first_name' => 'Andrea',
                'middle_name' => 'Santos',
                'birth_date' => '2002-11-30',
                'gender' => 'female',
                'civil_status' => 'single',
                'relationship_to_head' => 'sibling',
                'occupation' => 'private_employee',
                'monthly_income' => 18000,
                'educational_attainment' => 'college',
                'is_registered_voter' => true,
            ],
        ];

        foreach ($members as $index => $member) {
            $householdMember = Resident::withTrashed()->updateOrCreate(
                ['resident_id' => $member['resident_id']],
                $member + [
                    'household_id' => $resident->household_id,
                    'last_name' => $resident->last_name,
                    'contact_number' => '0917300000'.($index + 1),
                    'email' => 'resident3.member'.($index + 1).'@example.test',
                    'is_active' => true,
                    'notes' => 'Demo household member linked to resident ID 3.',
                ]
            );

            $householdMember->restore();
        }

        $resident->household->updateMemberCount();
        $resident->household->calculateTotalIncome();
    }
}
