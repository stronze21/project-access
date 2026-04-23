<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('signature_status')->default('pending')->after('signature');
        });
        // Update existing records - set completed for those with signatures
        DB::statement("UPDATE residents SET signature_status = 'completed' WHERE signature IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('signature_status');
        });
    }
};
