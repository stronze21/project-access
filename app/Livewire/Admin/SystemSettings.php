<?php

namespace App\Livewire\Admin;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class SystemSettings extends Component
{
    use WithFileUploads;
    use Toast;

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

    // For displaying current images
    public $current_logo_url = '';
    public $current_favicon_url = '';

    // Active tab
    public $activeTab = 'appearance';

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

    public function render()
    {
        return view('livewire.admin.system-settings');
    }
}