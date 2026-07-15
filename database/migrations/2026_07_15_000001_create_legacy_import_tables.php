<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('on_prem_legacy');
            $table->char('manifest_checksum', 64)->unique();
            $table->string('status')->default('staged');
            $table->json('file_manifest');
            $table->json('stats')->nullable();
            $table->json('error_summary')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_batch_id')
                ->constrained('legacy_import_batches')
                ->cascadeOnDelete();
            $table->string('source_table');
            $table->unsignedBigInteger('source_row_number');
            $table->string('natural_key')->nullable();
            $table->char('row_hash', 64);
            $table->json('raw_payload');
            $table->string('validation_status')->default('valid');
            $table->json('validation_errors')->nullable();
            $table->timestamps();

            $table->unique(
                ['legacy_import_batch_id', 'source_table', 'source_row_number'],
                'legacy_rows_batch_table_row_unique'
            );
            $table->index(
                ['legacy_import_batch_id', 'source_table', 'natural_key'],
                'legacy_rows_batch_table_key_index'
            );
            $table->index(['source_table', 'validation_status']);
        });

        Schema::create('legacy_resident_links', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('on_prem_legacy');
            $table->string('legacy_pin');
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_batch_id')
                ->nullable()
                ->constrained('legacy_import_batches')
                ->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('match_method')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['source_system', 'legacy_pin']);
        });

        Schema::create('legacy_household_links', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('on_prem_legacy');
            $table->string('legacy_family_number');
            $table->string('legacy_building_registry_number')->nullable();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_batch_id')
                ->nullable()
                ->constrained('legacy_import_batches')
                ->nullOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_system', 'legacy_family_number'],
                'legacy_household_source_family_unique'
            );
        });

        Schema::create('legacy_barangay_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_system')->default('on_prem_legacy');
            $table->string('legacy_code');
            $table->string('legacy_name');
            $table->string('brgy_code')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['source_system', 'legacy_code']);
            $table->index('brgy_code');
        });

        Schema::create('legacy_promotion_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_batch_id')
                ->constrained('legacy_import_batches')
                ->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('promoted_at');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_promotion_events');
        Schema::dropIfExists('legacy_barangay_mappings');
        Schema::dropIfExists('legacy_household_links');
        Schema::dropIfExists('legacy_resident_links');
        Schema::dropIfExists('legacy_import_rows');
        Schema::dropIfExists('legacy_import_batches');
    }
};
