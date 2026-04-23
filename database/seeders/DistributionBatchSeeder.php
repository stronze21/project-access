<?php

namespace Database\Seeders;

use App\Models\AyudaProgram;
use App\Models\DistributionBatch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DistributionBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating distribution batches...');

        $programs = AyudaProgram::all();
        $users = User::all();
        $barangays = LocationsSeeder::getBarangays();

        // Create ~30 distribution batches
        $batchCount = 0;

        foreach ($programs as $program) {
            // Skip the upcoming program (shouldn't have batches yet)
            if ($program->name === 'Community Development Fund') {
                continue;
            }

            // Determine how many batches to create for this program
            $batchesForProgram = match($program->frequency) {
                'one-time' => rand(1, 2),
                'weekly' => rand(2, 4),
                'monthly' => rand(2, 3),
                'quarterly' => rand(1, 2),
                'annual' => 1,
                default => rand(1, 2),
            };

            // Keep track of batch dates to ensure they're sequential
            $lastBatchDate = $program->start_date;

            for ($i = 0; $i < $batchesForProgram; $i++) {
                $batchCount++;

                // Determine batch date (after start date, before end date if any)
                $batchDate = $this->getNextBatchDate($program, $lastBatchDate);
                $lastBatchDate = $batchDate;

                // Skip if the batch date would be in the future
                if ($batchDate > now()) {
                    continue;
                }

                // Determine batch status based on date
                $status = $this->getBatchStatus($batchDate);

                // For completed batches, set actual beneficiaries
                $targetBeneficiaries = rand(20, 50);
                $actualBeneficiaries = ($status === 'completed') ? $targetBeneficiaries : rand(0, $targetBeneficiaries);

                // Calculate batch total amount
                $totalAmount = $actualBeneficiaries * $program->amount;

                // Create batch
                DistributionBatch::create([
                    'batch_number' => $program->code . '-' . date('Ymd', strtotime($batchDate)) . '-' . $batchCount,
                    'ayuda_program_id' => $program->id,
                    'location' => 'Distribution Center - ' . $barangays[array_rand($barangays)],
                    'batch_date' => $batchDate,
                    'start_time' => Carbon::parse($batchDate)->setTime(8, 0),
                    'end_time' => Carbon::parse($batchDate)->setTime(17, 0),
                    'target_beneficiaries' => $targetBeneficiaries,
                    'actual_beneficiaries' => $actualBeneficiaries,
                    'total_amount' => $totalAmount,
                    'status' => $status,
                    'created_by' => $users->random()->id,
                    'updated_by' => $users->random()->id,
                    'notes' => rand(0, 10) > 7 ? 'Notes for batch #' . $batchCount : null, // 30% have notes
                ]);
            }
        }

        $this->command->info('Created ' . $batchCount . ' distribution batches.');
    }

    /**
     * Get the next batch date based on program frequency
     */
    private function getNextBatchDate(AyudaProgram $program, Carbon $lastBatchDate): Carbon
    {
        $frequency = $program->frequency;

        // If this is the first batch, use the program start date
        if ($lastBatchDate->eq($program->start_date)) {
            return $program->start_date;
        }

        // Otherwise, calculate next date based on frequency
        $nextDate = match($frequency) {
            'weekly' => $lastBatchDate->copy()->addWeek(),
            'monthly' => $lastBatchDate->copy()->addMonth(),
            'quarterly' => $lastBatchDate->copy()->addMonths(3),
            'annual' => $lastBatchDate->copy()->addYear(),
            default => $lastBatchDate->copy()->addDays(rand(10, 30)), // For one-time programs, space them out
        };

        // If program has end date, ensure batch date isn't after it
        if ($program->end_date && $nextDate > $program->end_date) {
            return $program->end_date;
        }

        return $nextDate;
    }

    /**
     * Determine batch status based on date
     */
    private function getBatchStatus(Carbon $batchDate): string
    {
        // Batch in the past (completed)
        if ($batchDate < now()->subDay()) {
            return 'completed';
        }

        // Today's batch (ongoing)
        if ($batchDate->isToday()) {
            return 'ongoing';
        }

        // Tomorrow's batch (scheduled)
        if ($batchDate->isTomorrow()) {
            return 'scheduled';
        }

        // Future batch (scheduled)
        return 'scheduled';
    }
}