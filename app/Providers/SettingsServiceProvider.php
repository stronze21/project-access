<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('settings', function ($app) {
            return new \App\Services\SettingsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only load settings when the system_settings table exists
        try {
            if (\Schema::hasTable('system_settings')) {
                // Load all public settings for views
                View::composer('*', function ($view) {
                    $settings = \Cache::rememberForever('system_settings_public', function () {
                        return SystemSetting::where('is_public', true)
                            ->get()
                            ->pluck('value', 'key')
                            ->toArray();
                    });

                    $view->with('settings', $settings);
                });

                // Register a Blade directive to get settings
                Blade::directive('setting', function ($expression) {
                    return "<?php echo \App\Models\SystemSetting::get($expression); ?>";
});
}
} catch (\Exception $e) {
// Table doesn't exist yet, probably during migration
// Do nothing and continue with default values
}
}
}