<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->string('device_token');
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['resident_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_device_tokens');
    }
};
