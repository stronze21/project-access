<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->unique();
            $table->string('resident_identifier')->nullable();
            $table->string('resident_name')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('category', 80)->default('general');
            $table->string('subject');
            $table->text('message');
            $table->string('status', 50)->default('received');
            $table->string('source', 50)->default('web-form');
            $table->string('platform', 100)->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['resident_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('resident_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
