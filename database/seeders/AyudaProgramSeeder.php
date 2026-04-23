<?php

namespace Database\Seeders;

use App\Models\AyudaProgram;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AyudaProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating ayuda programs...');

        // Sample program data
        $programs = [
            [
                'name' => 'COVID-19 Financial Assistance',
                'code' => 'COVID-FA',
                'description' => 'Emergency financial assistance for families affected by the COVID-19 pandemic.',
                'type' => 'cash',
                'amount' => 5000.00,
                'start_date' => Carbon::now()->subMonths(10),
                'end_date' => Carbon::now()->addMonths(2),
                'frequency' => 'one-time',
                'distribution_count' => 1,
                'total_budget' => 5000000.00,
                'max_beneficiaries' => 1000,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Pantawid Pamilyang Pilipino Program',
                'code' => '4Ps',
                'description' => 'Conditional cash transfer program that provides grants to extremely poor households.',
                'type' => 'cash',
                'amount' => 3000.00,
                'start_date' => Carbon::now()->subYears(1),
                'end_date' => Carbon::now()->addYears(1),
                'frequency' => 'monthly',
                'distribution_count' => 12,
                'total_budget' => 3600000.00,
                'max_beneficiaries' => 100,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Food Subsidy Program',
                'code' => 'FSP',
                'description' => 'Distribution of essential food items to low-income households.',
                'type' => 'goods',
                'amount' => 2000.00,
                'goods_description' => 'Rice (10kg), Canned goods (10 pcs), Noodles (10 packs), Cooking oil (2L), Sugar (2kg), Salt (1kg)',
                'start_date' => Carbon::now()->subMonths(6),
                'end_date' => Carbon::now()->addMonths(6),
                'frequency' => 'monthly',
                'distribution_count' => 6,
                'total_budget' => 1200000.00,
                'max_beneficiaries' => 100,
                'requires_verification' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Senior Citizen Pension',
                'code' => 'SCP',
                'description' => 'Monthly pension for senior citizens aged 60 and above with no regular income.',
                'type' => 'cash',
                'amount' => 2000.00,
                'start_date' => Carbon::now()->subYears(2),
                'end_date' => null, // Ongoing
                'frequency' => 'monthly',
                'distribution_count' => 12,
                'total_budget' => 2400000.00,
                'max_beneficiaries' => 100,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Educational Assistance',
                'code' => 'EDA',
                'description' => 'Financial assistance for school-aged children from low-income families.',
                'type' => 'cash',
                'amount' => 3000.00,
                'start_date' => Carbon::now()->subMonths(8),
                'end_date' => Carbon::now()->addMonths(4),
                'frequency' => 'quarterly',
                'distribution_count' => 4,
                'total_budget' => 1200000.00,
                'max_beneficiaries' => 100,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare Support Program',
                'code' => 'HSP',
                'description' => 'Medical assistance and free check-ups for vulnerable populations.',
                'type' => 'services',
                'services_description' => 'Free medical check-up, basic laboratory tests, and maintenance medicines for common conditions.',
                'start_date' => Carbon::now()->subMonths(3),
                'end_date' => Carbon::now()->addMonths(9),
                'frequency' => 'quarterly',
                'distribution_count' => 4,
                'total_budget' => 1000000.00,
                'max_beneficiaries' => 200,
                'requires_verification' => false,
                'is_active' => true,
            ],
            [
                'name' => 'PWD Support Package',
                'code' => 'PWD-SP',
                'description' => 'Comprehensive support for persons with disabilities including cash and goods.',
                'type' => 'mixed',
                'amount' => 2500.00,
                'goods_description' => 'Assistive devices based on need assessment, medical supplies, and food packages.',
                'services_description' => 'Physical therapy sessions, psychosocial support, and skills training.',
                'start_date' => Carbon::now()->subMonths(5),
                'end_date' => Carbon::now()->addMonths(7),
                'frequency' => 'monthly',
                'distribution_count' => 12,
                'total_budget' => 600000.00,
                'max_beneficiaries' => 20,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Disaster Relief Assistance',
                'code' => 'DRA',
                'description' => 'Emergency assistance for families affected by natural disasters.',
                'type' => 'mixed',
                'amount' => 4000.00,
                'goods_description' => 'Emergency shelter kits, hygiene kits, food packs, and clothing.',
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->addMonths(4),
                'frequency' => 'one-time',
                'distribution_count' => 1,
                'total_budget' => 2000000.00,
                'max_beneficiaries' => 500,
                'requires_verification' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Farmers Assistance Program',
                'code' => 'FAP',
                'description' => 'Support for small-scale farmers with seeds, tools, and training.',
                'type' => 'goods',
                'amount' => 5000.00,
                'goods_description' => 'High-quality seeds, organic fertilizers, basic farming tools, and irrigation supplies.',
                'services_description' => 'Agricultural training workshops and consultations.',
                'start_date' => Carbon::now()->subMonths(4),
                'end_date' => Carbon::now()->addMonths(8),
                'frequency' => 'quarterly',
                'distribution_count' => 4,
                'total_budget' => 750000.00,
                'max_beneficiaries' => 150,
                'requires_verification' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Solo Parent Support',
                'code' => 'SPS',
                'description' => 'Comprehensive support package for solo parents.',
                'type' => 'cash',
                'amount' => 2500.00,
                'start_date' => Carbon::now()->subMonths(1),
                'end_date' => Carbon::now()->addMonths(11),
                'frequency' => 'monthly',
                'distribution_count' => 12,
                'total_budget' => 600000.00,
                'max_beneficiaries' => 20,
                'requires_verification' => true,
                'is_active' => true,
            ],
            // Creating a completed program for testing historical reports
            [
                'name' => 'COVID-19 First Wave Response',
                'code' => 'COVID-1',
                'description' => 'Emergency response program for the first wave of COVID-19 pandemic.',
                'type' => 'cash',
                'amount' => 4000.00,
                'start_date' => Carbon::now()->subYears(2),
                'end_date' => Carbon::now()->subYears(1),
                'frequency' => 'one-time',
                'distribution_count' => 1,
                'total_budget' => 2000000.00,
                'budget_used' => 2000000.00, // Fully used budget
                'max_beneficiaries' => 500,
                'current_beneficiaries' => 500, // All beneficiaries served
                'requires_verification' => false,
                'is_active' => false,
            ],
            // Creating an upcoming program for testing future planning
            [
                'name' => 'Community Development Fund',
                'code' => 'CDF',
                'description' => 'Support for community-led development initiatives.',
                'type' => 'cash',
                'amount' => 10000.00,
                'start_date' => Carbon::now()->addMonths(2),
                'end_date' => Carbon::now()->addYears(1),
                'frequency' => 'quarterly',
                'distribution_count' => 4,
                'total_budget' => 1000000.00,
                'max_beneficiaries' => 100,
                'requires_verification' => true,
                'is_active' => true,
            ],
        ];

        foreach ($programs as $program) {
            AyudaProgram::create($program);
        }

        $this->command->info('Created ' . count($programs) . ' ayuda programs.');
    }
}