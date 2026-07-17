<?php

namespace App\Console\Commands;

use App\Exceptions\BhwisUnavailableException;
use App\Services\Bhwis\BhwisGateway;
use Illuminate\Console\Command;

class CheckBhwisConnection extends Command
{
    protected $signature = 'bhwis:check';

    protected $description = 'Verify the read-only PDO ODBC BHWIS connection and required schema';

    public function handle(BhwisGateway $gateway): int
    {
        try {
            $missing = $gateway->checkSchema();
        } catch (BhwisUnavailableException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($missing !== []) {
            $this->error('BHWIS is reachable, but required schema items are missing:');
            foreach ($missing as $item) {
                $this->line(' - '.$item);
            }

            return self::FAILURE;
        }

        $this->info('BHWIS connection and required schema are ready.');

        return self::SUCCESS;
    }
}
