<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('text');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }

    /**
     * Seed default system settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            // Application information
            ['key' => 'app_name_1', 'value' => 'Alaminos City E-Services', 'group' => 'appearance', 'type' => 'text', 'is_public' => true],
            ['key' => 'app_name_2', 'value' => 'Solutions', 'group' => 'appearance', 'type' => 'text', 'is_public' => true],
            ['key' => 'app_logo', 'value' => 'logo.png', 'group' => 'appearance', 'type' => 'file', 'is_public' => true],
            ['key' => 'app_favicon', 'value' => 'favicon.ico', 'group' => 'appearance', 'type' => 'file', 'is_public' => true],

            // Location settings
            ['key' => 'municipality', 'value' => 'Alaminos City', 'group' => 'location', 'type' => 'text', 'is_public' => true],
            ['key' => 'province', 'value' => 'Pangasinan', 'group' => 'location', 'type' => 'text', 'is_public' => true],
            ['key' => 'region', 'value' => 'Region I', 'group' => 'location', 'type' => 'text', 'is_public' => true],
            ['key' => 'region_code', 'value' => '01', 'group' => 'location', 'type' => 'text', 'is_public' => false],
            ['key' => 'province_code', 'value' => '0155', 'group' => 'location', 'type' => 'text', 'is_public' => false],
            ['key' => 'municipality_code', 'value' => '015503', 'group' => 'location', 'type' => 'text', 'is_public' => false],

            // Contact information
            ['key' => 'contact_email', 'value' => 'contact@ayudahub.example', 'group' => 'contact', 'type' => 'email', 'is_public' => true],
            ['key' => 'contact_phone', 'value' => '+63 (XXX) XXX-XXXX', 'group' => 'contact', 'type' => 'text', 'is_public' => true],
            ['key' => 'office_address', 'value' => 'Municipal Hall, Sample Street', 'group' => 'contact', 'type' => 'textarea', 'is_public' => true],
        ];

        $table = app('db')->table('system_settings');

        foreach ($settings as $setting) {
            $table->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'group' => $setting['group'],
                'type' => $setting['type'],
                'is_public' => $setting['is_public'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
