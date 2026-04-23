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
        Schema::create('eligibility_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ayuda_program_id')->constrained('ayuda_programs')->onDelete('cascade');
            $table->string('criterion_name');
            $table->string('criterion_type'); // income, age, location, status, etc.
            $table->string('operator'); // >, <, =, >=, <=, in, not_in, etc.
            $table->string('value');
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligibility_criteria');
    }
};