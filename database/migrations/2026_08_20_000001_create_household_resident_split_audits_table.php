<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_resident_split_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('operation');
            $table->unsignedBigInteger('original_household_id')->nullable()->index();
            $table->unsignedBigInteger('new_household_id')->unique();
            $table->string('original_relationship_to_head')->nullable();
            $table->json('original_household_snapshot')->nullable();
            $table->json('distribution_household_mappings');
            $table->timestamp('converted_at');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_resident_split_audits');
    }
};
