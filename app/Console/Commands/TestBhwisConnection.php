<?php

namespace App\Console\Commands;

use App\Exceptions\BhwisUnavailableException;
use App\Services\Bhwis\BhwisGateway;
use Illuminate\Console\Command;

class TestBhwisConnection extends Command
{
    protected $signature = 'bhwis:test-connection';

    protected $description = 'Test the PDO ODBC BHWIS connection without displaying credentials';

    public function handle(BhwisGateway $gateway): int
    {
        try {
            $result = $gateway->testConnection();
        } catch (BhwisUnavailableException $exception) {
            $this->error('BHWIS connection failed: '.$exception->getMessage());
            $this->line('Use the supported CSV import while the live connection is unavailable.');

            return self::FAILURE;
        }

        $this->info('BHWIS connection successful.');
        $this->table(['Database', 'Server time'], [[
            $result['database_name'] ?? 'unknown', $result['server_time'] ?? 'unknown',
        ]]);

        return self::SUCCESS;
    }
}
