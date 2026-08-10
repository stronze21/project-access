<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_id_print_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barangay')->nullable();
            $table->string('status_filter', 20)->default('all');
            $table->unsignedInteger('batch_number')->default(1);
            $table->unsignedInteger('total_matching')->default(0);
            $table->unsignedInteger('resident_count')->default(0);
            $table->string('status', 30)->default('generated');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            $table->index(['barangay', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('resident_id_print_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_batch_id')->constrained('resident_id_print_batches')->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('resident_pin');
            $table->string('resident_name');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            $table->unique(['print_batch_id', 'resident_id']);
            $table->index('resident_pin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_id_print_batch_items');
        Schema::dropIfExists('resident_id_print_batches');
    }
};
