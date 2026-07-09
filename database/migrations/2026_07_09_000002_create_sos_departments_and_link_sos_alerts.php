<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sos_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->string('hotline', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('sos_alerts', function (Blueprint $table) {
            $table
                ->foreignId('sos_department_id')
                ->nullable()
                ->after('resident_id')
                ->constrained('sos_departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sos_department_id');
        });

        Schema::dropIfExists('sos_departments');
    }
};
