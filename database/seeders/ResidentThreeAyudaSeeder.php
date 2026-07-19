<?php

namespace Database\Seeders;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\Resident;
use Illuminate\Database\Seeder;

class ResidentThreeAyudaSeeder extends Seeder
{
    public function run(): void
    {
        $resident = Resident::query()->find(3);

        if (! $resident) {
            $this->command?->warn('Resident ID 3 was not found; Ayuda demo data was not seeded.');

            return;
        }

        $rows = [
            [
                'program' => [
                    'code' => 'R3-MEDICAL-AID',
                    'name' => 'Medical Assistance Program',
                    'description' => 'Financial support for medicine, laboratory work, and other qualified medical expenses.',
                    'type' => 'cash',
                    'amount' => 5000,
                    'start_date' => now()->subMonths(2),
                    'end_date' => now()->addMonths(10),
                    'frequency' => 'one-time',
                    'distribution_count' => 1,
                    'total_budget' => 500000,
                    'max_beneficiaries' => 100,
                    'requires_verification' => true,
                    'is_active' => true,
                ],
                'distribution' => [
                    'reference_number' => 'D-RESIDENT3-MEDICAL',
                    'distribution_date' => now()->addDays(5),
                    'amount' => 5000,
                    'status' => 'pending',
                    'notes' => 'Application received and awaiting document verification.',
                ],
            ],
            [
                'program' => [
                    'code' => 'R3-FOOD-PACK',
                    'name' => 'Family Food Pack Assistance',
                    'description' => 'Essential food supplies for qualified resident households.',
                    'type' => 'goods',
                    'amount' => 2500,
                    'goods_description' => 'Rice, canned goods, noodles, cooking oil, and other household staples.',
                    'start_date' => now()->subMonth(),
                    'end_date' => now()->addMonths(5),
                    'frequency' => 'monthly',
                    'distribution_count' => 6,
                    'total_budget' => 750000,
                    'max_beneficiaries' => 300,
                    'requires_verification' => true,
                    'is_active' => true,
                ],
                'distribution' => [
                    'reference_number' => 'D-RESIDENT3-FOOD',
                    'distribution_date' => now()->addDays(2),
                    'amount' => 2500,
                    'goods_details' => 'One family food pack ready for scheduled release.',
                    'status' => 'verified',
                    'notes' => 'Resident eligibility verified. Awaiting distribution schedule.',
                ],
            ],
            [
                'program' => [
                    'code' => 'R3-EDUCATION-AID',
                    'name' => 'Educational Cash Assistance',
                    'description' => 'Cash assistance for school supplies, uniforms, and transportation expenses.',
                    'type' => 'cash',
                    'amount' => 3000,
                    'start_date' => now()->subMonths(4),
                    'end_date' => now()->addMonths(8),
                    'frequency' => 'quarterly',
                    'distribution_count' => 4,
                    'total_budget' => 600000,
                    'max_beneficiaries' => 200,
                    'requires_verification' => false,
                    'is_active' => true,
                ],
                'distribution' => [
                    'reference_number' => 'D-RESIDENT3-EDUCATION',
                    'distribution_date' => now()->subDays(7),
                    'amount' => 3000,
                    'status' => 'distributed',
                    'notes' => 'Educational assistance released to the resident.',
                    'verification_data' => ['source' => 'demo-seeder', 'resident_id' => 3],
                ],
            ],
        ];

        foreach ($rows as $row) {
            $programData = $row['program'];
            $program = AyudaProgram::withTrashed()->updateOrCreate(
                ['code' => $programData['code']],
                $programData
            );
            $program->restore();

            $distribution = Distribution::withTrashed()->updateOrCreate(
                ['reference_number' => $row['distribution']['reference_number']],
                $row['distribution'] + [
                    'ayuda_program_id' => $program->id,
                    'resident_id' => $resident->id,
                    'household_id' => $resident->household_id,
                    'batch_id' => null,
                    'distributed_by' => null,
                    'verified_by' => null,
                ]
            );
            $distribution->restore();

            $distributed = Distribution::query()
                ->where('ayuda_program_id', $program->id)
                ->where('status', 'distributed');

            $program->update([
                'budget_used' => $distributed->sum('amount'),
                'current_beneficiaries' => (clone $distributed)->distinct()->count('resident_id'),
            ]);
        }
    }
}
