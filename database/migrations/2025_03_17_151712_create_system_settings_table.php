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
            ['key' => 'app_name_1', 'value' => "Alaminos City Citizen's E-Services", 'group' => 'appearance', 'type' => 'text', 'is_public' => true],
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

            // Android app release information
            ['key' => 'mobile_app.name', 'value' => 'ProjectAccess Mobile', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            ['key' => 'mobile_app.description', 'value' => 'ProjectAccess Mobile gives residents a direct way to use local digital services, receive announcements, submit requests, and stay connected with city programs.', 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            ['key' => 'mobile_app.version_name', 'value' => '1.0.0', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            ['key' => 'mobile_app.version_code', 'value' => '1', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            ['key' => 'mobile_app.release_notes', 'value' => 'Initial public Android release.', 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            ['key' => 'mobile_app.features', 'value' => json_encode([
                'Resident account access and profile management',
                'Announcements from the city and barangay offices',
                'Citizen service requests and tracking',
                'Emergency alerts and grievance reporting',
                'Public feedback, complaints, polls, and sentiment features',
            ]), 'group' => 'mobile_app', 'type' => 'textarea', 'is_public' => true],
            ['key' => 'mobile_app.source_project_path', 'value' => 'C:\\Users\\HP\\source\\repos\\ProjectAccessApp\\ProjectAccessApp', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => false],
            ['key' => 'mobile_app.apk_path', 'value' => '', 'group' => 'mobile_app', 'type' => 'file', 'is_public' => true],
            ['key' => 'mobile_app.apk_original_name', 'value' => '', 'group' => 'mobile_app', 'type' => 'text', 'is_public' => true],
            ['key' => 'mobile_app.apk_size', 'value' => '', 'group' => 'mobile_app', 'type' => 'number', 'is_public' => true],
            ['key' => 'mobile_app.apk_uploaded_at', 'value' => '', 'group' => 'mobile_app', 'type' => 'datetime', 'is_public' => true],
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
