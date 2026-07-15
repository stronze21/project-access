<?php

namespace App\Console\Commands;

use App\Models\LegacyImportBatch;
use App\Services\Legacy\LegacyDataPromoter;
use Illuminate\Console\Command;
use Throwable;

class PromoteLegacyDataCommand extends Command
{
    protected $signature = 'legacy:promote
                            {batch : Staged legacy-import batch ID}
                            {--commit : Apply safe creates/backfills; otherwise report proposed results}';

    protected $description = 'Promote validated legacy rows without overwriting populated canonical fields';

    public function handle(LegacyDataPromoter $promoter): int
    {
        $batch = LegacyImportBatch::find($this->argument('batch'));
        if (! $batch) {
            $this->error('Legacy import batch not found.');

            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        try {
            $result = $promoter->promote($batch, $commit);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($commit
            ? "Batch #{$batch->id} promotion completed."
            : "Batch #{$batch->id} promotion dry run completed; no canonical rows were changed.");

        foreach (['references', 'residents', 'households', 'bhw'] as $section) {
            $this->newLine();
            $this->comment(strtoupper($section));
            $this->table(
                ['Metric', 'Count'],
                collect($result[$section])->map(fn ($value, $key) => [$key, $value])->values()->all()
            );
        }

        if (! $commit) {
            $this->comment('Review these counts, then repeat with --commit to apply guarded promotion.');
        }

        return self::SUCCESS;
    }
}
