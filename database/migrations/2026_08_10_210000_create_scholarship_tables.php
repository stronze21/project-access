<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('academic_year')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->string('office_address')->nullable();
            $table->string('office_hours')->nullable();
            $table->json('required_originals')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scholarship_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('label');
            $table->string('applicant_type'); // new | ongoing | both
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['code', 'applicant_type'], 'scholarship_doc_types_code_applicant_uq');
        });

        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('scholarship_program_id');
            $table->string('reference_number')->unique();
            $table->string('applicant_type'); // new | ongoing
            $table->string('status')->default('draft');
            $table->decimal('gwa', 5, 2)->nullable();
            $table->string('award_tier')->nullable(); // academic_excellence | academic_achievement
            $table->text('rejection_reason')->nullable();
            $table->text('staff_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('conditionally_approved_at')->nullable();
            $table->timestamp('physically_verified_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('scholarship_program_id', 'scholarship_apps_program_fk')
                ->references('id')->on('scholarship_programs')->cascadeOnDelete();
            $table->index(['resident_id', 'scholarship_program_id', 'status'], 'scholarship_apps_resident_program_status_idx');
            $table->index('status', 'scholarship_apps_status_idx');
        });

        Schema::create('scholarship_application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_application_id');
            $table->unsignedBigInteger('document_type_id');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('virus_scan_status')->default('pending');
            $table->string('virus_scan_message')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('status')->default('uploaded'); // uploaded | verified | rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('scholarship_application_id', 'scholarship_app_docs_app_fk')
                ->references('id')->on('scholarship_applications')->cascadeOnDelete();
            $table->foreign('document_type_id', 'scholarship_app_docs_type_fk')
                ->references('id')->on('scholarship_document_types')->cascadeOnDelete();
            $table->index(['scholarship_application_id', 'document_type_id'], 'scholarship_app_docs_app_type_idx');
        });

        Schema::create('scholarship_application_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scholarship_application_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->nullable(); // resident | staff | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('scholarship_application_id', 'scholarship_app_events_app_fk')
                ->references('id')->on('scholarship_applications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_application_events');
        Schema::dropIfExists('scholarship_application_documents');
        Schema::dropIfExists('scholarship_applications');
        Schema::dropIfExists('scholarship_document_types');
        Schema::dropIfExists('scholarship_programs');
    }
};
