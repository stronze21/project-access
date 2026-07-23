<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ModuleSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_default_to_enabled_when_settings_are_missing(): void
    {
        $modules = app(ModuleSettings::class);

        $this->assertTrue($modules->enabled('bosesmoto'));
        $this->assertTrue($modules->enabled('complaints'));
        $this->assertTrue($modules->enabled('sentiments'));
        $this->assertTrue($modules->enabled('polls'));
    }

    public function test_disabling_master_module_blocks_bosesmoto_web_and_mobile_api_routes(): void
    {
        app(ModuleSettings::class)->set('bosesmoto', false);

        $this->getJson('/api/mobile/modules')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'bosesmoto',
                'enabled' => false,
            ]);

        $this->actingAs(User::factory()->create())
            ->get('/bosesmoto/dashboard')
            ->assertNotFound();

        $this->postJson('/api/mobile/auth/login', [])
            ->assertNotFound()
            ->assertJsonPath('message', 'This module is currently unavailable.');
    }

    public function test_disabling_submodules_blocks_matching_web_and_api_routes(): void
    {
        $modules = app(ModuleSettings::class);
        $modules->set('complaints', false);
        $modules->set('sentiments', false);
        $modules->set('polls', false);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->get('/complaints')->assertNotFound();
        $this->getJson('/api/mobile/complaints')->assertNotFound();

        $this->actingAs($user)->get('/sentiments')->assertNotFound();
        $this->getJson('/api/mobile/sentiments')->assertNotFound();

        $this->actingAs($user)->get('/polls')->assertNotFound();
        $this->getJson('/api/mobile/polls')->assertNotFound();
    }

    public function test_system_settings_component_saves_module_toggles(): void
    {
        Livewire::test(SystemSettings::class)
            ->set('moduleStates.bosesmoto', true)
            ->set('moduleStates.complaints', true)
            ->set('moduleStates.sentiments', false)
            ->set('moduleStates.polls', true)
            ->call('saveModuleSettings');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'modules.bosesmoto.sentiments.enabled',
            'value' => '0',
            'group' => 'modules',
            'type' => 'boolean',
            'is_public' => false,
        ]);

        SystemSetting::clearCache();

        $this->assertFalse(app(ModuleSettings::class)->enabled('sentiments'));
    }

    public function test_system_settings_store_dynamic_mail_password_encrypted(): void
    {
        Livewire::test(SystemSettings::class)
            ->set('mail_dynamic_enabled', true)
            ->set('mail_mailer', 'smtp')
            ->set('mail_host', 'smtp.example.test')
            ->set('mail_port', 587)
            ->set('mail_username', 'mailer')
            ->set('mail_password', 'secret-password')
            ->set('mail_scheme', 'smtp')
            ->set('mail_from_address', 'noreply@example.test')
            ->set('mail_from_name', 'Project ACCESS')
            ->call('saveMailSettings')
            ->assertHasNoErrors();

        $stored = SystemSetting::where('key', 'mail.password')->value('value');
        $this->assertNotSame('secret-password', $stored);
        $this->assertSame('secret-password', Crypt::decryptString($stored));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
    }
}
