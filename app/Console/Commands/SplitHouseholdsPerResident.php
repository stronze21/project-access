<?php

namespace App\Console\Commands;

use App\Services\HouseholdResidentSplitService;
use Illuminate\Console\Command;

class SplitHouseholdsPerResident extends Command
{
    protected $signature = 'residents:split-households
                            {--dry-run : Report changes without writing to the database}';

    protected $description = 'Give every resident a dedicated household and reassign their distributions';

    public function handle(HouseholdResidentSplitService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = $service->run($dryRun);

        $this->components->info($dryRun ? 'Household conversion dry run complete.' : 'Household conversion complete.');
        $this->table(['Change', 'Count'], [
            ['Residents reassigned', $summary['residents']],
            ['Households created', $summary['households_created']],
            ['Old households archived', $summary['households_archived']],
            ['Distributions reassigned', $summary['distributions']],
        ]);

        return self::SUCCESS;
    }
}
