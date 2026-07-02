<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_anonymous_submission')->default(false);
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('title');
            $table->string('short_summary', 280);
            $table->text('description');
            $table->foreignId('category_id')->constrained('complaint_categories');
            $table->string('visibility')->default('public_anonymous');
            $table->foreignId('barangay_id')->nullable()->constrained('bosesmoto_barangays')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('received')->index();
            $table->string('priority')->nullable()->index();
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('moderation_status')->default('normal')->index();
            $table->text('moderation_reason')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('first_action_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->timestamp('citizen_confirmed_at')->nullable();
            $table->timestamp('auto_closed_at')->nullable();
            $table->timestamp('due_ack_at')->nullable();
            $table->timestamp('due_first_action_at')->nullable();
            $table->timestamp('due_resolution_at')->nullable();
            $table->timestamp('ack_overdue_notified_at')->nullable();
            $table->timestamp('first_action_overdue_notified_at')->nullable();
            $table->timestamp('resolution_overdue_notified_at')->nullable();
            $table->timestamp('mayor_notified_at')->nullable();
            $table->string('submitted_ip', 45)->nullable()->index();
            $table->unsignedInteger('support_count')->default(0);
            $table->timestamps();
        });

        Schema::create('complaint_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_override')->default(false);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('complaint_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_override')->default(false);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('complaint_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_staff_response')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->foreignId('hidden_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('complaint_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['complaint_id', 'user_id']);
        });

        Schema::create('complaint_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('evidence');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('virus_scan_status')->default('pending');
            $table->string('virus_scan_message')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('complaint_public_official', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('public_official_id')->constrained('public_officials')->cascadeOnDelete();
            $table->unique(['complaint_id', 'public_official_id']);
        });

        Schema::create('complaint_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaint_audit_logs');
        Schema::dropIfExists('complaint_public_official');
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaint_internal_notes');
        Schema::dropIfExists('complaint_supports');
        Schema::dropIfExists('complaint_comments');
        Schema::dropIfExists('complaint_status_histories');
        Schema::dropIfExists('complaint_assignments');
        Schema::dropIfExists('complaints');
    }
};
