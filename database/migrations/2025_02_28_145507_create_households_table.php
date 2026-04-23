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
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('household_id')->unique();
            $table->string('address');
            $table->string('barangay');
            $table->string('city_municipality');
            $table->string('province');
            $table->string('postal_code')->nullable();
            $table->string('region')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->integer('member_count')->default(1);
            $table->enum('dwelling_type', ['owned', 'rented', 'shared', 'informal', 'member','other'])->nullable();
            $table->boolean('has_electricity')->default(true);
            $table->boolean('has_water_supply')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};