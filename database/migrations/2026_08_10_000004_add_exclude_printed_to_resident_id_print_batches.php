<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_id_print_batches', function (Blueprint $table) {
            $table->boolean('exclude_printed')->default(true)->after('status_filter');
        });
    }

    public function down(): void
    {
        Schema::table('resident_id_print_batches', function (Blueprint $table) {
            $table->dropColumn('exclude_printed');
        });
    }
};
