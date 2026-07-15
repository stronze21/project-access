<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->boolean('is_provisional')->default(false)->after('building_registry_number')->index();
            $table->string('provisional_for_pin')->nullable()->after('is_provisional')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropUnique(['provisional_for_pin']);
            $table->dropIndex(['is_provisional']);
            $table->dropColumn(['is_provisional', 'provisional_for_pin']);
        });
    }
};
