<?php

namespace App\Console\Commands;

use App\Models\BiometricWorkstation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BiometricWorkstationCommand extends Command
{
    protected $signature = 'biometrics:workstation {name? : Workstation label} {--revoke= : UUID to revoke}';

    protected $description = 'Pair a fingerprint workstation or revoke its credential';

    public function handle(): int
    {
        if ($id = $this->option('revoke')) {
            $count = BiometricWorkstation::whereKey($id)->update(['active' => false]);
            $this->info($count ? 'Workstation revoked.' : 'Workstation not found.');

            return $count ? self::SUCCESS : self::FAILURE;
        }
        $name = $this->argument('name');
        if (! is_string($name) || trim($name) === '' || strlen($name) > 255) {
            $this->error('Supply a workstation label (1–255 characters).');

            return self::FAILURE;
        }
        $token = Str::random(64);
        $workstation = BiometricWorkstation::create([
            'id' => (string) Str::uuid(), 'name' => trim($name), 'token_hash' => hash('sha256', $token),
        ]);
        $this->info('Save these values in the protected Windows service configuration. The token is shown once.');
        $this->line('WorkstationId: '.$workstation->id);
        $this->line('Token: '.$token);

        return self::SUCCESS;
    }
}
