<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_identity_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['photo', 'signature']);
            $table->string('requested_file_path')->nullable();
            $table->longText('requested_signature')->nullable();
            $table->text('request_reason');
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['resident_id', 'type', 'status'], 'identity_request_resident_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_identity_change_requests');
    }
};
