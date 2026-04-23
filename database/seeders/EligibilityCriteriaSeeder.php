<?php

namespace Database\Seeders;

use App\Models\AyudaProgram;
use App\Models\EligibilityCriteria;
use Illuminate\Database\Seeder;

class EligibilityCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating eligibility criteria...');

        // Get all programs
        $programs = AyudaProgram::all();

        // Create criteria for each program
        foreach ($programs as $program) {
            $this->createCriteriaForProgram($program);
        }

        $this->command->info('Created eligibility criteria for ' . $programs->count() . ' programs.');
    }

    /**
     * Create appropriate criteria for each program type
     */
    private function createCriteriaForProgram(AyudaProgram $program)
    {
        // Common criteria templates that can be reused
        $criteriaTemplates = [
            // Income criteria
            'low_income' => [
                'criterion_name' => 'Low Income',
                'criterion_type' => 'monthly_income',
                'operator' => 'less_than_or_equal',
                'value' => '10000',
                'is_required' => true,
            ],
            'poverty_threshold' => [
                'criterion_name' => 'Below Poverty Threshold',
                'criterion_type' => 'monthly_income',
                'operator' => 'less_than_or_equal',
                'value' => '12500',
                'is_required' => true,
            ],
            'household_income' => [
                'criterion_name' => 'Low Household Income',
                'criterion_type' => 'household_income',
                'operator' => 'less_than_or_equal',
                'value' => '30000',
                'is_required' => false,
            ],

            // Demographic criteria
            'senior_citizen' => [
                'criterion_name' => 'Senior Citizen',
                'criterion_type' => 'senior',
                'operator' => 'equals',
                'value' => 'true',
                'is_required' => true,
            ],
            'pwd' => [
                'criterion_name' => 'Person with Disability',
                'criterion_type' => 'pwd',
                'operator' => 'equals',
                'value' => 'true',
                'is_required' => true,
            ],
            'solo_parent' => [
                'criterion_name' => 'Solo Parent',
                'criterion_type' => 'solo_parent',
                'operator' => 'equals',
                'value' => 'true',
                'is_required' => true,
            ],
            'indigenous' => [
                'criterion_name' => 'Indigenous Person',
                'criterion_type' => 'indigenous',
                'operator' => 'equals',
                'value' => 'true',
                'is_required' => true,
            ],

            // Age criteria
            'adult' => [
                'criterion_name' => 'Adult',
                'criterion_type' => 'age',
                'operator' => 'greater_than_or_equal',
                'value' => '18',
                'is_required' => true,
            ],
            'school_age' => [
                'criterion_name' => 'School Age Child',
                'criterion_type' => 'age',
                'operator' => 'between',
                'value' => '5,21',
                'is_required' => true,
            ],
            'youth' => [
                'criterion_name' => 'Youth',
                'criterion_type' => 'age',
                'operator' => 'between',
                'value' => '15,30',
                'is_required' => true,
            ],

            // Location criteria
            'urban_barangay' => [
                'criterion_name' => 'Urban Barangay',
                'criterion_type' => 'barangay',
                'operator' => 'in',
                'value' => 'Barangay 1,Barangay 2,Barangay 3,Barangay 4,Barangay 5',
                'is_required' => false,
            ],
            'rural_barangay' => [
                'criterion_name' => 'Rural Barangay',
                'criterion_type' => 'barangay',
                'operator' => 'in',
                'value' => 'Barangay 6,Barangay 7,Barangay 8,Barangay 9,Barangay 10',
                'is_required' => false,
            ],
            'priority_barangay' => [
                'criterion_name' => 'Priority Barangay',
                'criterion_type' => 'barangay',
                'operator' => 'in',
                'value' => 'Barangay 11,Barangay 12,Barangay 13,Barangay 14,Barangay 15',
                'is_required' => false,
            ],

            // Other criteria
            'registered_voter' => [
                'criterion_name' => 'Registered Voter',
                'criterion_type' => 'voter',
                'operator' => 'equals',
                'value' => 'true',
                'is_required' => false,
            ],
            'farmer' => [
                'criterion_name' => 'Farmer',
                'criterion_type' => 'occupation',
                'operator' => 'equals',
                'value' => 'farmer',
                'is_required' => true,
            ],
            'unemployed' => [
                'criterion_name' => 'Unemployed',
                'criterion_type' => 'occupation',
                'operator' => 'in',
                'value' => 'unemployed,student',
                'is_required' => false,
            ],
        ];

        // Choose criteria based on program name/type
        $criteriaToCreate = [];

        switch ($program->name) {
            case 'COVID-19 Financial Assistance':
            case 'COVID-19 First Wave Response':
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                $criteriaToCreate[] = $criteriaTemplates['adult'];
                break;

            case 'Pantawid Pamilyang Pilipino Program':
                $criteriaToCreate[] = $criteriaTemplates['poverty_threshold'];
                $criteriaToCreate[] = $criteriaTemplates['household_income'];
                break;

            case 'Food Subsidy Program':
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                $criteriaToCreate[] = $criteriaTemplates['household_income'];
                $criteriaToCreate[] = [
                    'criterion_name' => 'Large Household',
                    'criterion_type' => 'household_size',
                    'operator' => 'greater_than_or_equal',
                    'value' => '4',
                    'is_required' => false,
                ];
                break;

            case 'Senior Citizen Pension':
                $criteriaToCreate[] = $criteriaTemplates['senior_citizen'];
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                break;

            case 'Educational Assistance':
                $criteriaToCreate[] = $criteriaTemplates['school_age'];
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                $criteriaToCreate[] = [
                    'criterion_name' => 'Currently Enrolled',
                    'criterion_type' => 'educational_attainment',
                    'operator' => 'not_equals',
                    'value' => 'none',
                    'is_required' => true,
                ];
                break;

            case 'Healthcare Support Program':
                // No strict criteria, just some priorities
                $criteriaToCreate[] = [
                    'criterion_name' => 'Priority Age Groups',
                    'criterion_type' => 'age',
                    'operator' => 'not_between',
                    'value' => '15,45',
                    'is_required' => false,
                ];
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                break;

            case 'PWD Support Package':
                $criteriaToCreate[] = $criteriaTemplates['pwd'];
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                break;

            case 'Disaster Relief Assistance':
                // Often emergency-based, so minimal criteria
                $criteriaToCreate[] = $criteriaTemplates['adult'];
                $criteriaToCreate[] = [
                    'criterion_name' => 'Priority Barangays',
                    'criterion_type' => 'barangay',
                    'operator' => 'in',
                    'value' => 'Barangay 1,Barangay 2,Barangay 5,Barangay 8,Barangay 12',
                    'is_required' => false,
                ];
                break;

            case 'Farmers Assistance Program':
                $criteriaToCreate[] = $criteriaTemplates['farmer'];
                $criteriaToCreate[] = $criteriaTemplates['adult'];
                $criteriaToCreate[] = $criteriaTemplates['rural_barangay'];
                break;

            case 'Solo Parent Support':
                $criteriaToCreate[] = $criteriaTemplates['solo_parent'];
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                break;

            case 'Community Development Fund':
                $criteriaToCreate[] = $criteriaTemplates['adult'];
                $criteriaToCreate[] = $criteriaTemplates['registered_voter'];
                $criteriaToCreate[] = [
                    'criterion_name' => 'Community Representative',
                    'criterion_type' => 'occupation',
                    'operator' => 'not_in',
                    'value' => 'unemployed,student,retired',
                    'is_required' => true,
                ];
                break;

            default:
                // Default criteria for any other program
                $criteriaToCreate[] = $criteriaTemplates['low_income'];
                $criteriaToCreate[] = $criteriaTemplates['adult'];
                break;
        }

        // Create all the selected criteria
        foreach ($criteriaToCreate as $criteria) {
            $criteria['ayuda_program_id'] = $program->id;
            EligibilityCriteria::create($criteria);
        }
    }
}