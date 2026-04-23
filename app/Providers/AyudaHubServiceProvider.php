<?php

namespace App\Providers;

use App\Services\QrCodeService;
use App\Services\RfidService;
use Illuminate\Support\ServiceProvider;

class AyudaHubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register QR Code Service
        $this->app->singleton(QrCodeService::class, function ($app) {
            return new QrCodeService();
        });

        // Register RFID Service
        $this->app->singleton(RfidService::class, function ($app) {
            return new RfidService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}