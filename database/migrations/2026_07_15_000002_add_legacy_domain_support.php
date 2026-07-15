<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_income_types', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('residents', function (Blueprint $table) {
            $table->foreignId('source_income_type_id')
                ->nullable()
                ->after('occupation')
                ->constrained('source_income_types')
                ->nullOnDelete();
            $table->string('ethnicity')->nullable()->after('is_indigenous');
            $table->timestamp('locked_at')->nullable()->after('last_login_at');
        });

        Schema::table('households', function (Blueprint $table) {
            $table->string('building_registry_number')->nullable()->after('household_id');
            $table->index('building_registry_number');
        });

        Schema::create('barangay_zones', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('on_prem_legacy');
            $table->string('legacy_barangay_code');
            $table->string('legacy_zone_id');
            $table->string('name');
            $table->string('brgy_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['source_system', 'legacy_barangay_code', 'legacy_zone_id'],
                'barangay_zones_legacy_identity_unique'
            );
            $table->index('brgy_code');
        });

        Schema::create('barangay_health_worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_batch_id')
                ->nullable()
                ->constrained('legacy_import_batches')
                ->nullOnDelete();
            $table->string('legacy_pin');
            $table->string('assignment_slot');
            $table->timestamps();

            $table->unique(
                ['barangay_zone_id', 'legacy_pin', 'assignment_slot'],
                'bhw_zone_pin_slot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangay_health_worker_assignments');
        Schema::dropIfExists('barangay_zones');

        Schema::table('households', function (Blueprint $table) {
            $table->dropIndex(['building_registry_number']);
            $table->dropColumn('building_registry_number');
        });

        Schema::table('residents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_income_type_id');
            $table->dropColumn(['ethnicity', 'locked_at']);
        });

        Schema::dropIfExists('source_income_types');
    }
};
