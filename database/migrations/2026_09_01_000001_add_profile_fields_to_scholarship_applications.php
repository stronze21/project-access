<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->string('course')->nullable()->after('applicant_type');
            $table->string('father_name')->nullable()->after('course');
            $table->string('father_occupation')->nullable()->after('father_name');
            $table->string('mother_name')->nullable()->after('father_occupation');
            $table->string('mother_occupation')->nullable()->after('mother_name');
        });
    }

    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->dropColumn([
                'course',
                'father_name',
                'father_occupation',
                'mother_name',
                'mother_occupation',
            ]);
        });
    }
};
