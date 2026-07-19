<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('citizen_service_types')->insert([
            ['code' => 'certificate', 'name' => 'Certificate', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'permit', 'name' => 'Permit', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'assistance', 'name' => 'Assistance', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'business-permit', 'name' => 'Business Permit', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'civil-registry', 'name' => 'Civil Registry', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tax-payment', 'name' => 'Tax Payment', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'financial-aid', 'name' => 'Financial Aid', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'general', 'name' => 'General Service', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'other', 'name' => 'Other', 'sort_order' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_service_types');
    }
};
