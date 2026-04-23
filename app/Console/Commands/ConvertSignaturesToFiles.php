<?php

namespace App\Console\Commands;

use App\Models\Resident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ConvertSignaturesToFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'residents:convert-signatures
                            {--force : Force conversion even if file already exists}
                            {--dry-run : Show what would be done without making changes}
                            {--chunk=10 : Number of records to process at once}
                            {--limit= : Limit the number of records to process}
                            {--memory=256M : Memory limit for this command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert base64 signature data to files and update database paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set memory limit
        $memoryLimit = $this->option('memory');
        ini_set('memory_limit', $memoryLimit);
        $this->info("Memory limit set to: {$memoryLimit}");

        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Create the signature directory if it doesn't exist
        $signatureDir = public_path('residents/signature');
        if (!$isDryRun && !File::exists($signatureDir)) {
            File::makeDirectory($signatureDir, 0755, true);
            $this->info("✓ Created directory: {$signatureDir}");
        }

        // Build query for counting
        $query = Resident::whereNotNull('signature')
            ->where('signature', 'like', 'data:%');

        // Apply limit if specified
        if ($limit) {
            $query->limit($limit);
        }

        // Count total residents with base64 signatures
        $totalCount = $limit
            ? min($limit, Resident::whereNotNull('signature')->where('signature', 'like', 'data:%')->count())
            : Resident::whereNotNull('signature')->where('signature', 'like', 'data:%')->count();

        if ($totalCount === 0) {
            $this->info('✓ No signatures to convert.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalCount} signature(s) to convert");
        if ($limit) {
            $this->info("Limiting to {$limit} record(s)");
        }
        $this->info("Processing in chunks of {$chunkSize}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $processed = 0;

        // Process in chunks to avoid memory issues
        Resident::whereNotNull('signature')
            ->where('signature', 'like', 'data:%')
            ->select(['id', 'signature']) // Only select needed columns
            ->when($limit, function ($query) use ($limit) {
                return $query->limit($limit);
            })
            ->chunk($chunkSize, function ($residents) use (&$converted, &$skipped, &$failed, &$processed, $isDryRun, $force, $progressBar, $limit) {
                foreach ($residents as $resident) {
                    // Check if we've reached the limit
                    if ($limit && $processed >= $limit) {
                        return false; // Stop chunking
                    }

                    try {
                        $result = $this->convertSignature($resident, $isDryRun, $force);

                        if ($result === 'converted') {
                            $converted++;
                        } elseif ($result === 'skipped') {
                            $skipped++;
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        $this->newLine();
                        $this->error("✗ Failed for Resident #{$resident->id}: " . $e->getMessage());
                        $progressBar->display();
                    }

                    $processed++;
                    $progressBar->advance();
                }

                // Force garbage collection after each chunk
                gc_collect_cycles();
            });

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Converted', $converted],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total', $totalCount],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Convert a single resident's signature
     *
     * @param Resident $resident
     * @param bool $isDryRun
     * @param bool $force
     * @return string 'converted', 'skipped', or 'failed'
     */
    protected function convertSignature(Resident $resident, bool $isDryRun, bool $force): string
    {
        // Extract the base64 data from the data URL
        $signatureData = $resident->signature;

        // Parse the data URL
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $signatureData, $matches)) {
            throw new \Exception('Invalid signature format');
        }

        $extension = $matches[1]; // png, jpg, etc.
        $base64Data = $matches[2];

        // Decode the base64 data
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            throw new \Exception('Failed to decode base64 data');
        }

        // Generate filename using resident ID and timestamp
        $filename = "signature_{$resident->id}_" . time() . ".{$extension}";
        $relativePath = "residents/signature/{$filename}";
        $fullPath = public_path($relativePath);

        // Check if file already exists
        if (!$force && File::exists($fullPath)) {
            // Free memory
            unset($imageData, $base64Data, $signatureData);
            return 'skipped';
        }

        if ($isDryRun) {
            // Just log what would be done
            $size = strlen($imageData);
            // Free memory
            unset($imageData, $base64Data, $signatureData);
            return 'converted';
        }

        // Save the file
        if (!File::put($fullPath, $imageData)) {
            throw new \Exception('Failed to write file to disk');
        }

        // Update the database with the new path
        $resident->signature = $relativePath;
        $resident->save();

        // Free memory
        unset($imageData, $base64Data, $signatureData);

        return 'converted';
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
