<?php

use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('special_sector')->nullable()->after('is_indigenous');
            $table->string('birthplace')->nullable()->after('birth_date');
            $table->date('date_issue')->nullable()->after('signature');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('special_sector');
            $table->dropColumn('birthplace');
            $table->dropColumn('date_issue');
        });
    }
};