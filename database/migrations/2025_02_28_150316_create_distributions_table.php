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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('ayuda_program_id')->constrained();
            $table->foreignId('resident_id')->constrained();
            $table->foreignId('household_id')->nullable()->constrained();
            $table->foreignId('batch_id')->nullable()->constrained('distribution_batches');
            $table->foreignId('distributed_by')->nullable()->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->date('distribution_date');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('goods_details')->nullable();
            $table->string('services_details')->nullable();
            $table->enum('status', ['pending', 'verified', 'distributed', 'rejected', 'cancelled'])->default('pending');
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('verification_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};