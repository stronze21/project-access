<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('complaints:enforce-sla')->hourly();
Schedule::command('complaints:auto-close')->dailyAt('01:30');

Schedule::call(function () {
    \App\Models\BiometricSession::where('expires_at', '<', now())->delete();
})->hourly()->name('biometrics:prune-sessions');
