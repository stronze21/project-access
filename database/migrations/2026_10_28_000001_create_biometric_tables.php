<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL commits DDL independently. Preserve tables created before a
        // failed attempt so this migration can resume without losing data.
        if (! Schema::hasTable('resident_fingerprints')) {
            Schema::create('resident_fingerprints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('resident_id')->unique()->constrained()->cascadeOnDelete();
                $table->text('template');
                $table->string('finger_position', 30);
                $table->string('format', 80);
                $table->string('sdk_version', 100);
                $table->uuid('revision');
                $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('consented_at');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('biometric_workstations')) {
            Schema::create('biometric_workstations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('token_hash', 64);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('biometric_sessions')) {
            Schema::create('biometric_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('workstation_id')->constrained('biometric_workstations')->cascadeOnDelete();
                $table->string('purpose', 20);
                $table->foreignId('resident_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('finger_position', 30)->nullable();
                $table->dateTime('consented_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->json('candidates')->nullable();
                $table->foreignId('matched_resident_id')->nullable()->constrained('residents')->nullOnDelete();
                $table->string('outcome', 30)->nullable();
                $table->dateTime('expires_at')->index();
                $table->timestamps();
            });
        }

        $permission = Permission::firstOrCreate(['name' => 'manage-resident-fingerprints', 'guard_name' => 'web']);
        Role::where('name', 'system-administrator')->first()?->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_sessions');
        Schema::dropIfExists('biometric_workstations');
        Schema::dropIfExists('resident_fingerprints');
        Permission::where('name', 'manage-resident-fingerprints')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
