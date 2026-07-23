<?php

namespace App\Livewire\Admin;

use App\Models\SystemSetting;
use App\Services\DynamicMailConfig;
use App\Services\ModuleSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class SystemSettings extends Component
{
    use Toast;
    use WithFileUploads;

    // Form fields - all explicitly defined
    public $app_name_1 = '';

    public $app_name_2 = '';

    public $app_logo;

    public $app_favicon;

    public $municipality = '';

    public $province = '';

    public $region = '';

    public $region_code = '';

    public $province_code = '';

    public $municipality_code = '';

    public $contact_email = '';

    public $contact_phone = '';

    public $office_address = '';

    public bool $mail_dynamic_enabled = false;

    public string $mail_mailer = 'smtp';

    public string $mail_host = '';

    public int $mail_port = 587;

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_scheme = 'smtp';

    public string $mail_from_address = '';

    public string $mail_from_name = 'Project ACCESS';

    public string $mail_test_recipient = '';

    public bool $mail_password_configured = false;

    // For displaying current images
    public $current_logo_url = '';

    public $current_favicon_url = '';

    // Active tab
    public $activeTab = 'appearance';

    public array $moduleStates = [];

    public function mount()
    {
        $this->loadAllSettings();
    }

    public function loadAllSettings()
    {
        // Get all settings at once for efficiency
        $settings = SystemSetting::all();

        // Manually assign each setting to the component property
        foreach ($settings as $setting) {
            if (property_exists($this, $setting->key)) {
                $this->{$setting->key} = $setting->value;
            }
        }

        // Get image URLs separately
        $logo = SystemSetting::where('key', 'app_logo')->first();
        $favicon = SystemSetting::where('key', 'app_favicon')->first();

        if ($logo && $logo->value) {
            $this->current_logo_url = Storage::url($logo->value);
        }

        if ($favicon && $favicon->value) {
            $this->current_favicon_url = Storage::url($favicon->value);
        }

        $this->loadModuleSettings();
        $this->loadMailSettings();
    }

    public function loadMailSettings(): void
    {
        $this->mail_dynamic_enabled = SystemSetting::get('mail.dynamic_enabled', '0') === '1';
        $this->mail_mailer = SystemSetting::get('mail.mailer', 'smtp');
        $this->mail_host = SystemSetting::get('mail.host', '');
        $this->mail_port = (int) SystemSetting::get('mail.port', '587');
        $this->mail_username = SystemSetting::get('mail.username', '');
        $this->mail_scheme = SystemSetting::get('mail.scheme', 'smtp');
        $this->mail_from_address = SystemSetting::get('mail.from_address', '');
        $this->mail_from_name = SystemSetting::get('mail.from_name', 'Project ACCESS');
        $this->mail_password = '';
        $this->mail_password_configured = (bool) SystemSetting::get('mail.password');
        if ($this->mail_test_recipient === '') {
            $this->mail_test_recipient = $this->mail_from_address;
        }
    }

    public function saveMailSettings(DynamicMailConfig $dynamicMail): void
    {
        $validated = $this->validate([
            'mail_dynamic_enabled' => ['boolean'],
            'mail_mailer' => ['required', 'in:smtp,log'],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_scheme' => ['nullable', 'in:smtp,smtps'],
            'mail_from_address' => ['required', 'email:rfc', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        foreach ([
            'dynamic_enabled' => $validated['mail_dynamic_enabled'] ? '1' : '0',
            'mailer' => $validated['mail_mailer'], 'host' => $validated['mail_host'] ?? '',
            'port' => (string) $validated['mail_port'], 'username' => $validated['mail_username'] ?? '',
            'scheme' => $validated['mail_scheme'] ?? 'smtp', 'from_address' => $validated['mail_from_address'],
            'from_name' => $validated['mail_from_name'],
        ] as $key => $value) {
            SystemSetting::set("mail.{$key}", $value);
        }
        if ($this->mail_password !== '') {
            SystemSetting::set('mail.password', $dynamicMail->encryptPassword($this->mail_password));
        }

        $dynamicMail->apply();
        app('mail.manager')->purge($this->mail_mailer);
        $this->loadMailSettings();
        $this->success('Email configuration saved. New web requests use it immediately; restart queue workers if applicable.');
    }

    public function sendTestEmail(DynamicMailConfig $dynamicMail): void
    {
        $this->validate(['mail_test_recipient' => ['required', 'email:rfc', 'max:255']]);
        $dynamicMail->apply();
        app('mail.manager')->purge(config('mail.default'));
        Mail::raw('This is a Project ACCESS email configuration test.', function ($message): void {
            $message->to($this->mail_test_recipient)->subject('Project ACCESS email test');
        });
        $this->success('Test email sent to '.$this->mail_test_recipient.'.');
    }

    public function loadModuleSettings(): void
    {
        $this->moduleStates = collect(app(ModuleSettings::class)->all())
            ->mapWithKeys(fn (array $module, string $key) => [$key => (bool) $module['enabled']])
            ->toArray();
    }

    public function changeTab($tab)
    {
        $this->activeTab = $tab;

        // Reload all settings to ensure we have fresh data
        $this->loadAllSettings();
    }

    public function saveAppearanceSettings()
    {
        // Upload logo if provided
        if ($this->app_logo && is_object($this->app_logo) && method_exists($this->app_logo, 'store')) {
            $logoPath = $this->app_logo->store('system', 'public');
            SystemSetting::where('key', 'app_logo')->update(['value' => $logoPath]);
        }

        // Upload favicon if provided
        if ($this->app_favicon && is_object($this->app_favicon) && method_exists($this->app_favicon, 'store')) {
            $faviconPath = $this->app_favicon->store('system', 'public');
            SystemSetting::where('key', 'app_favicon')->update(['value' => $faviconPath]);
        }

        // Update app names
        SystemSetting::where('key', 'app_name_1')->update(['value' => $this->app_name_1]);
        SystemSetting::where('key', 'app_name_2')->update(['value' => $this->app_name_2]);

        // Reset file uploads
        $this->app_logo = null;
        $this->app_favicon = null;

        // Clear any cached settings
        \Cache::forget('system_settings_all');
        \Cache::forget('system_settings_grouped');
        \Cache::forget('system_settings_public');

        $this->success('Appearance settings updated successfully!');

        // Reload settings
        return redirect()->route('admin.system-settings');
    }

    public function saveLocationSettings()
    {
        SystemSetting::where('key', 'municipality')->update(['value' => $this->municipality]);
        SystemSetting::where('key', 'province')->update(['value' => $this->province]);
        SystemSetting::where('key', 'region')->update(['value' => $this->region]);
        SystemSetting::where('key', 'region_code')->update(['value' => $this->region_code]);
        SystemSetting::where('key', 'province_code')->update(['value' => $this->province_code]);
        SystemSetting::where('key', 'municipality_code')->update(['value' => $this->municipality_code]);

        // Reload settings
        $this->loadAllSettings();

        $this->success('Location settings updated successfully!');
    }

    public function saveContactSettings()
    {
        SystemSetting::where('key', 'contact_email')->update(['value' => $this->contact_email]);
        SystemSetting::where('key', 'contact_phone')->update(['value' => $this->contact_phone]);
        SystemSetting::where('key', 'office_address')->update(['value' => $this->office_address]);

        // Reload settings
        $this->loadAllSettings();

        $this->success('Contact settings updated successfully!');
    }

    public function saveModuleSettings()
    {
        $modules = app(ModuleSettings::class);

        foreach (array_keys(ModuleSettings::MODULES) as $module) {
            $modules->set($module, (bool) ($this->moduleStates[$module] ?? false));
        }

        $this->loadModuleSettings();

        $this->success('Module settings updated successfully!');
    }

    public function render()
    {
        return view('livewire.admin.system-settings', [
            'availableModules' => ModuleSettings::MODULES,
        ]);
    }
}
