<?php

namespace Database\Seeders;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DistributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating distributions...');

        $programs = AyudaProgram::all();
        $batches = DistributionBatch::all();
        $residents = Resident::all();
        $users = User::all();

        // Create distributions for batches
        $batchDistributionCount = $this->createBatchDistributions($batches, $users, $residents);

        // Create some individual distributions (not part of batches)
        $individualDistributionCount = $this->createIndividualDistributions($programs, $users, $residents);

        $this->command->info('Created ' . $batchDistributionCount . ' batch distributions and ' . $individualDistributionCount . ' individual distributions.');

        // Update program stats
        $this->updateProgramStats();
    }

    /**
     * Create distributions for existing batches
     */
    private function createBatchDistributions($batches, $users, $residents)
    {
        $distributionCount = 0;

        foreach ($batches as $batch) {
            $program = $batch->ayudaProgram;
            $batchDate = $batch->batch_date;

            // Skip future batches
            if ($batchDate > now()) {
                continue;
            }

            // For each batch, create distributions up to the actual_beneficiaries count
            for ($i = 0; $i < $batch->actual_beneficiaries; $i++) {
                $distributionCount++;

                // Get a random resident, but check eligibility for the program
                $eligibleResidents = $this->getEligibleResidents($program, $residents, 10);
                if (empty($eligibleResidents)) {
                    continue; // Skip if no eligible residents found
                }
                $resident = $eligibleResidents[array_rand($eligibleResidents)];

                // Create the distribution
                $this->createDistribution(
                    program: $program,
                    resident: $resident,
                    users: $users,
                    date: $batchDate,
                    status: $batch->status === 'completed' ? 'distributed' : 'pending',
                    batchId: $batch->id
                );
            }
        }

        return $distributionCount;
    }

    /**
     * Create individual distributions not part of batches
     */
    private function createIndividualDistributions($programs, $users, $residents)
    {
        $distributionCount = 0;

        // Create ~300 individual distributions
        for ($i = 0; $i < 300; $i++) {
            $distributionCount++;

            // Get a random program
            $program = $programs->random();

            // Skip future programs
            if ($program->start_date > now()) {
                continue;
            }

            // Get eligible residents
            $eligibleResidents = $this->getEligibleResidents($program, $residents, 10);
            if (empty($eligibleResidents)) {
                continue; // Skip if no eligible residents found
            }
            $resident = $eligibleResidents[array_rand($eligibleResidents)];

            // Generate a random date within the program's timeframe
            $startDate = max($program->start_date, now()->subMonths(6));
            $endDate = $program->end_date ? min($program->end_date, now()) : now();
            $distributionDate = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));

            // Create the distribution
            $this->createDistribution(
                program: $program,
                resident: $resident,
                users: $users,
                date: $distributionDate,
                status: 'distributed'
            );
        }

        return $distributionCount;
    }

    /**
     * Create a distribution record
     */
    private function createDistribution($program, $resident, $users, $date, $status = 'distributed', $batchId = null)
    {
        $amount = $program->amount;

        // Add some variation to the amount for non-cash types
        if ($program->type !== 'cash') {
            $amount = $amount * (1 + (rand(-10, 10) / 100)); // ±10% variation
        }

        // Different details based on program type
        $goodsDetails = null;
        $servicesDetails = null;

        if ($program->type === 'goods' || $program->type === 'mixed') {
            $goodsDetails = $program->goods_description;
        }

        if ($program->type === 'services' || $program->type === 'mixed') {
            $servicesDetails = $program->services_description;
        }

        // For verified distributions, add a verifier
        $verifiedBy = null;
        if ($status === 'distributed' && $program->requires_verification) {
            $verifiedBy = $users->random()->id;
        }

        // Create the distribution
        Distribution::create([
            'reference_number' => Distribution::generateReferenceNumber($date),
            'ayuda_program_id' => $program->id,
            'resident_id' => $resident->id,
            'household_id' => $resident->household_id,
            'batch_id' => $batchId,
            'distributed_by' => $users->random()->id,
            'verified_by' => $verifiedBy,
            'distribution_date' => $date,
            'amount' => $amount,
            'goods_details' => $goodsDetails,
            'services_details' => $servicesDetails,
            'status' => $status,
            'notes' => rand(0, 10) > 7 ? 'Notes for this distribution.' : null, // 30% have notes
        ]);
    }

    /**
     * Get eligible residents for a program
     */
    private function getEligibleResidents($program, $residents, $limit = 5)
    {
        $eligibleResidents = [];
        $count = 0;

        // Use the shuffled residents to get more variation
        $shuffledResidents = $residents->shuffle();

        foreach ($shuffledResidents as $resident) {
            if ($count >= $limit) {
                break;
            }

            // Basic eligibility checks based on program name
            $isEligible = true;

            // Check eligibility based on program name/criteria
            if (str_contains($program->name, 'Senior') && !$resident->is_senior_citizen) {
                $isEligible = false;
            } elseif (str_contains($program->name, 'PWD') && !$resident->is_pwd) {
                $isEligible = false;
            } elseif (str_contains($program->name, 'Solo Parent') && !$resident->is_solo_parent) {
                $isEligible = false;
            } elseif (str_contains($program->name, 'Educational') && $resident->getAge() < 5) {
                $isEligible = false;
            } elseif (str_contains($program->name, 'Farmer') && $resident->occupation !== 'farmer') {
                $isEligible = false;
            }

            // Add other checks as needed

            if ($isEligible) {
                $eligibleResidents[] = $resident;
                $count++;
            }
        }

        return $eligibleResidents;
    }

    /**
     * Update program statistics based on distributions
     */
    private function updateProgramStats()
    {
        $this->command->info('Updating program statistics...');

        $programs = AyudaProgram::all();

        foreach ($programs as $program) {
            // Calculate total distributed amount
            $totalDistributed = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', 'distributed')
                ->sum('amount');

            // Calculate unique beneficiaries
            $uniqueBeneficiaries = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', 'distributed')
                ->distinct('resident_id')
                ->count('resident_id');

            // Update program stats
            $program->budget_used = $totalDistributed;
            $program->current_beneficiaries = $uniqueBeneficiaries;
            $program->save();
        }

        $this->command->info('Program statistics updated.');
    }
}
