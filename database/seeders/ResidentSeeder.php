<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\Household;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating residents...');

        $households = Household::all();

        $firstNames = [
            'Maria', 'Juan', 'Ana', 'Jose', 'Sofia', 'Luis', 'Rosa', 'Carlos', 'Elena', 'Antonio',
            'Pedro', 'Luisa', 'Manuel', 'Teresa', 'Rafael', 'Lourdes', 'Francisco', 'Josefina', 'Miguel', 'Carmen',
            'Eduardo', 'Margarita', 'Roberto', 'Dolores', 'Ricardo', 'Rosario', 'Javier', 'Isabel', 'Alberto', 'Pilar',
            'Gabriel', 'Concepcion', 'Rodrigo', 'Gloria', 'Victor', 'Trinidad', 'Raul', 'Guadalupe', 'Emilio', 'Aurora'
        ];

        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Ramos', 'Gonzales', 'Flores', 'Fernandez',
            'De la Cruz', 'Diaz', 'Del Rosario', 'Hernandez', 'Lopez', 'Perez', 'Dela Peña', 'Marquez', 'Navarro', 'Villanueva',
            'Rodriguez', 'Bautista', 'Aquino', 'Castro', 'Domingo', 'Martinez', 'Villegas', 'Rivera', 'Robles', 'Salazar',
            'Gutierrez', 'Morales', 'Romero', 'Miranda', 'Valdez', 'Santiago', 'De Guzman', 'Espiritu', 'Pascual', 'Padilla'
        ];

        $middleNames = [
            'De la Cruz', 'Santos', 'Reyes', 'Garcia', 'Gonzales', 'Torres', 'Ramos', 'Cruz', 'Rivera', 'Diaz',
            'Mendoza', 'Flores', 'Lopez', 'Perez', 'Rodriguez', 'Aquino', 'Castro', 'Domingo', 'Navarro', 'Salazar'
        ];

        $relationships = ['head', 'spouse', 'child', 'sibling', 'parent', 'grandchild', 'grandparent', 'in-law', 'other_relative', 'non-relative'];
        $genders = ['male', 'female'];
        $civilStatuses = ['single', 'married', 'widowed', 'divorced', 'separated'];
        $educationalAttainments = ['elementary', 'high_school', 'vocational', 'college', 'graduate', 'none'];
        $occupations = ['teacher', 'farmer', 'laborer', 'driver', 'vendor', 'factory_worker', 'government_employee', 'private_employee', 'business_owner', 'unemployed', 'retired', 'student'];

        // Create ~500 residents (~3-4 per household on average)
        $residentCount = 0;

        foreach ($households as $household) {
            // Each household will have 2-6 members
            $memberCount = rand(2, 6);

            // First member is the household head
            for ($i = 0; $i < $memberCount; $i++) {
                $residentCount++;

                $gender = $genders[array_rand($genders)];
                $firstName = ($gender === 'male')
                    ? $firstNames[array_rand(array_slice($firstNames, 0, 20))]
                    : $firstNames[array_rand(array_slice($firstNames, 20, 20))];

                $lastName = $lastNames[array_rand($lastNames)];

                // 70% have middle names
                $middleName = rand(0, 10) < 7 ? $middleNames[array_rand($middleNames)] : null;

                // Determine age bucket for this resident
                // 0: senior (60+), 1: adult (18-59), 2: youth (13-17), 3: child (0-12)
                $ageBucket = $this->getRandomAgeBucketByDistribution();

                // Generate appropriate birth date
                $birthDate = $this->generateBirthDateByAgeBucket($ageBucket);

                // Age-appropriate civil status
                $civilStatus = $this->getCivilStatusByAge($birthDate);

                // Determine relationship to head
                $relationship = ($i === 0)
                    ? 'head'
                    : $this->getRelationshipByAgeBucket($ageBucket);

                // Set appropriate occupation based on age
                $occupation = $this->getOccupationByAge($birthDate);

                // Educational attainment based on age
                $educationalAttainment = $this->getEducationalAttainmentByAge($birthDate);

                // Monthly income based on occupation and educational attainment
                $monthlyIncome = $this->getMonthlyIncomeByOccupation($occupation, $educationalAttainment);

                // Special demographics with appropriate age checks
                $isSeniorCitizen = Carbon::parse($birthDate)->age >= 60;

                // PWD status weighted slightly higher among seniors
                $isPWD = rand(0, 100) < ($isSeniorCitizen ? 25 : 10);

                // Solo parent status (only for adults, not applicable to children/seniors)
                $isSoloParent = ($civilStatus !== 'married' && Carbon::parse($birthDate)->age >= 18 && Carbon::parse($birthDate)->age < 60) ? (rand(0, 100) < 20) : false;

                // Pregnant status (only for females of reproductive age)
                $isPregnant = ($gender === 'female' && Carbon::parse($birthDate)->age >= 18 && Carbon::parse($birthDate)->age <= 45) ? (rand(0, 100) < 8) : false;

                // Lactating status (only for females of reproductive age, slightly less common than pregnant)
                $isLactating = ($gender === 'female' && Carbon::parse($birthDate)->age >= 18 && Carbon::parse($birthDate)->age <= 45 && !$isPregnant) ? (rand(0, 100) < 5) : false;

                // Indigenous status (consistent across household members)
                $isIndigenous = (rand(0, 100) < 15); // 15% chance

                // Contact info (only for those 15+)
                $hasContactInfo = Carbon::parse($birthDate)->age >= 15;
                $contactNumber = $hasContactInfo ? '09' . rand(100000000, 999999999) : null;
                $email = $hasContactInfo && rand(0, 10) > 5
                    ? strtolower($firstName) . '.' . strtolower($lastName) . '@' . ['gmail.com', 'yahoo.com', 'example.com'][array_rand([0, 1, 2])]
                    : null;

                Resident::create([
                    'household_id' => $household->id,
                    'resident_id' => 'R-' . date('Ym') . '-' . str_pad($residentCount, 4, '0', STR_PAD_LEFT),
                    'qr_code' => 'QR-R-' . strtoupper(substr(md5($residentCount . time()), 0, 10)),
                    'rfid_number' => rand(0, 10) > 7 ? strtoupper(bin2hex(random_bytes(6))) : null, // 30% have RFID
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'suffix' => rand(0, 20) === 0 ? ['Jr.', 'Sr.', 'III', 'IV'][array_rand([0, 1, 2, 3])] : null, // 5% have suffix
                    'birth_date' => $birthDate,
                    'gender' => $gender,
                    'civil_status' => $civilStatus,
                    'contact_number' => $contactNumber,
                    'email' => $email,
                    'relationship_to_head' => $relationship,
                    'occupation' => $occupation,
                    'monthly_income' => $monthlyIncome,
                    'educational_attainment' => $educationalAttainment,
                    'is_registered_voter' => Carbon::parse($birthDate)->age >= 18 && rand(0, 10) > 2, // 80% of adults are registered voters
                    'is_pwd' => $isPWD,
                    'is_senior_citizen' => $isSeniorCitizen,
                    'is_solo_parent' => $isSoloParent,
                    'is_pregnant' => $isPregnant,
                    'is_lactating' => $isLactating,
                    'is_indigenous' => $isIndigenous,
                    'is_active' => true,
                    'notes' => rand(0, 10) > 7 ? 'Notes for resident #' . $residentCount : null, // 30% have notes
                ]);
            }

            // Update household member count
            $household->member_count = $memberCount;
            $household->save();
        }

        $this->command->info('Created ' . $residentCount . ' residents.');
    }

    /**
     * Get random age bucket with appropriate distribution:
     * - Seniors (60+): 15%
     * - Adults (18-59): 55%
     * - Youth (13-17): 15%
     * - Children (0-12): 15%
     */
    private function getRandomAgeBucketByDistribution()
    {
        $rand = rand(1, 100);

        if ($rand <= 15) {
            return 0; // Senior
        } elseif ($rand <= 70) {
            return 1; // Adult
        } elseif ($rand <= 85) {
            return 2; // Youth
        } else {
            return 3; // Child
        }
    }

    /**
     * Generate a birth date based on the age bucket
     */
    private function generateBirthDateByAgeBucket($ageBucket)
    {
        $now = Carbon::now();

        switch ($ageBucket) {
            case 0: // Senior (60+)
                return $now->copy()->subYears(rand(60, 90))->subDays(rand(0, 365));
            case 1: // Adult (18-59)
                return $now->copy()->subYears(rand(18, 59))->subDays(rand(0, 365));
            case 2: // Youth (13-17)
                return $now->copy()->subYears(rand(13, 17))->subDays(rand(0, 365));
            case 3: // Child (0-12)
                return $now->copy()->subYears(rand(0, 12))->subDays(rand(0, 365));
            default:
                return $now->copy()->subYears(rand(18, 59));
        }
    }

    /**
     * Get an appropriate civil status based on age
     */
    private function getCivilStatusByAge($birthDate)
    {
        $age = Carbon::parse($birthDate)->age;

        if ($age < 18) {
            return 'single';
        }

        if ($age < 25) {
            // 80% single, 20% married for young adults
            return rand(0, 100) < 80 ? 'single' : 'married';
        }

        if ($age < 40) {
            // 40% single, 50% married, 5% widowed, 5% separated for adults
            $rand = rand(0, 100);
            if ($rand < 40) return 'single';
            if ($rand < 90) return 'married';
            if ($rand < 95) return 'widowed';
            return 'separated';
        }

        if ($age < 60) {
            // 25% single, 55% married, 10% widowed, 10% separated for middle-aged
            $rand = rand(0, 100);
            if ($rand < 25) return 'single';
            if ($rand < 80) return 'married';
            if ($rand < 90) return 'widowed';
            return 'separated';
        }

        // Seniors: 15% single, 50% married, 30% widowed, 5% separated
        $rand = rand(0, 100);
        if ($rand < 15) return 'single';
        if ($rand < 65) return 'married';
        if ($rand < 95) return 'widowed';
        return 'separated';
    }

    /**
     * Get relationship to household head based on age bucket
     */
    private function getRelationshipByAgeBucket($ageBucket)
    {
        switch ($ageBucket) {
            case 0: // Senior (60+)
                $relationships = ['parent', 'in-law', 'spouse', 'other_relative'];
                $weights = [50, 20, 25, 5]; // Parents are more likely for seniors
                break;
            case 1: // Adult (18-59)
                $relationships = ['spouse', 'sibling', 'child', 'in-law', 'other_relative', 'non-relative'];
                $weights = [40, 20, 30, 5, 3, 2]; // Spouses and adult children are common
                break;
            case 2: // Youth (13-17)
                $relationships = ['child', 'other_relative', 'non-relative'];
                $weights = [85, 10, 5]; // Mostly children
                break;
            case 3: // Child (0-12)
                $relationships = ['child', 'grandchild', 'other_relative'];
                $weights = [80, 15, 5]; // Mostly children or grandchildren
                break;
            default:
                $relationships = ['child', 'spouse', 'sibling'];
                $weights = [40, 40, 20];
        }

        // Simple weighted random selection
        $rand = rand(1, array_sum($weights));
        $cumulative = 0;

        foreach ($relationships as $index => $relationship) {
            $cumulative += $weights[$index];
            if ($rand <= $cumulative) {
                return $relationship;
            }
        }

        return $relationships[0]; // Fallback
    }

    /**
     * Get an appropriate occupation based on age
     */
    private function getOccupationByAge($birthDate)
    {
        $age = Carbon::parse($birthDate)->age;

        if ($age < 15) {
            return 'student'; // Children are students
        }

        if ($age < 18) {
            return rand(0, 10) > 1 ? 'student' : ['laborer', 'vendor', 'unemployed'][array_rand([0, 1, 2])];
        }

        if ($age < 23) {
            // Young adults: mix of students and workers
            $occupations = ['student', 'laborer', 'vendor', 'factory_worker', 'private_employee', 'unemployed'];
            $weights = [40, 15, 10, 15, 10, 10];
        } elseif ($age < 60) {
            // Working adults: wide range of occupations
            $occupations = [
                'teacher', 'farmer', 'laborer', 'driver', 'vendor', 'factory_worker',
                'government_employee', 'private_employee', 'business_owner', 'unemployed'
            ];
            $weights = [10, 15, 15, 10, 10, 15, 5, 10, 5, 5];
        } else {
            // Seniors: mostly retired, some still working
            $occupations = ['retired', 'farmer', 'vendor', 'business_owner', 'unemployed'];
            $weights = [60, 10, 10, 5, 15];
        }

        // Simple weighted random selection
        $rand = rand(1, array_sum($weights));
        $cumulative = 0;

        foreach ($occupations as $index => $occupation) {
            $cumulative += $weights[$index];
            if ($rand <= $cumulative) {
                return $occupation;
            }
        }

        return $occupations[0]; // Fallback
    }

    /**
     * Get educational attainment appropriate for age
     */
    private function getEducationalAttainmentByAge($birthDate)
    {
        $age = Carbon::parse($birthDate)->age;

        if ($age < 6) {
            return 'none'; // Too young for school
        }

        if ($age < 12) {
            return 'elementary'; // Elementary age
        }

        if ($age < 16) {
            // Mostly elementary, some in high school
            return rand(0, 10) > 3 ? 'elementary' : 'high_school';
        }

        if ($age < 18) {
            // Mostly high school
            return rand(0, 10) > 2 ? 'high_school' : 'elementary';
        }

        if ($age < 22) {
            // Mix of high school, vocational, and college
            $rand = rand(0, 10);
            if ($rand < 4) return 'high_school';
            if ($rand < 7) return 'vocational';
            return 'college';
        }

        if ($age < 60) {
            // Adults: wide distribution
            $rand = rand(0, 100);
            if ($rand < 5) return 'none';
            if ($rand < 25) return 'elementary';
            if ($rand < 55) return 'high_school';
            if ($rand < 75) return 'vocational';
            if ($rand < 95) return 'college';
            return 'graduate';
        }

        // Seniors: lower educational attainment on average (historical reality)
        $rand = rand(0, 100);
        if ($rand < 15) return 'none';
        if ($rand < 45) return 'elementary';
        if ($rand < 75) return 'high_school';
        if ($rand < 90) return 'vocational';
        if ($rand < 98) return 'college';
        return 'graduate';
    }

    /**
     * Get monthly income based on occupation and education
     */
    private function getMonthlyIncomeByOccupation($occupation, $educationalAttainment)
    {
        // Base income by occupation
        $incomeByOccupation = [
            'teacher' => 25000,
            'farmer' => 15000,
            'laborer' => 12000,
            'driver' => 15000,
            'vendor' => 12000,
            'factory_worker' => 15000,
            'government_employee' => 22000,
            'private_employee' => 20000,
            'business_owner' => 30000,
            'unemployed' => 0,
            'retired' => 8000,
            'student' => 0,
        ];

        // Income multiplier by education
        $educationMultiplier = [
            'none' => 0.7,
            'elementary' => 0.8,
            'high_school' => 1.0,
            'vocational' => 1.2,
            'college' => 1.5,
            'graduate' => 2.0,
        ];

        $baseIncome = $incomeByOccupation[$occupation] ?? 10000;
        $multiplier = $educationMultiplier[$educationalAttainment] ?? 1.0;

        // Calculate base income with education adjustment
        $calculatedIncome = $baseIncome * $multiplier;

        // Add random variation (±15%)
        $variation = $calculatedIncome * (rand(-15, 15) / 100);

        return round($calculatedIncome + $variation);
    }
}