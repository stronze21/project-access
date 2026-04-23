<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_type', 100);
            $table->string('service_name');
            $table->string('reference_number')->unique();
            $table->string('status', 50)->default('submitted');
            $table->string('current_step', 150)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('expected_completion_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['resident_id', 'status']);
            $table->index(['service_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_service_requests');
    }
};
