<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_id_print_batches', function (Blueprint $table) {
            $table->boolean('alphabetical')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('resident_id_print_batches', function (Blueprint $table) {
            $table->dropColumn('alphabetical');
        });
    }
};
