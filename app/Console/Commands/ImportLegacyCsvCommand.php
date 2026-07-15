<?php

namespace App\Console\Commands;

use App\Services\Legacy\LegacyCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyCsvCommand extends Command
{
    protected $signature = 'legacy:import
                            {paths* : One or more legacy CSV files or directories}
                            {--commit : Persist a staged import batch; otherwise run validation only}';

    protected $description = 'Validate and stage legacy on-prem CSV exports without changing canonical records';

    public function handle(LegacyCsvImporter $importer): int
    {
        $dryRun = ! $this->option('commit');

        try {
            $result = $importer->import($this->argument('paths'), $dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['already_imported']) {
            $this->warn("These exact files are already staged as batch #{$result['batch_id']}.");

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? 'Dry run completed; no database rows were written.'
            : "Legacy files staged as batch #{$result['batch_id']}.");

        $rows = [];
        foreach ($result['stats'] as $sourceTable => $stats) {
            $rows[] = [
                $sourceTable,
                $stats['rows'],
                $stats['valid_rows'],
                $stats['invalid_rows'],
                $stats['conflict_rows'],
                $stats['duplicate_natural_keys'],
            ];
        }
        $this->table(['Source table', 'Rows', 'Valid', 'Invalid', 'Conflicts', 'Duplicate keys'], $rows);

        foreach ($result['error_summary'] as $label => $summary) {
            if (! $summary['checked']) {
                $this->line("{$label}: not checked (personal-info file not included)");
            } elseif ($summary['count'] > 0) {
                $this->warn("{$label}: {$summary['count']}");
            }
        }

        $this->line('Manifest checksum: '.$result['manifest_checksum']);
        if ($dryRun) {
            $this->comment('Run the same command with --commit to stage the validated rows.');
        }

        return self::SUCCESS;
    }
}
