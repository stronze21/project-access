<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_attainments', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('civil_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_code')->unique();
            $table->string('name');
            $table->string('canonical_value');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('residents', function (Blueprint $table) {
            $table->boolean('is_legacy_imported')->default(false)->after('is_bhw')->index();
        });

        DB::table('residents')
            ->whereIn('id', DB::table('legacy_promotion_events')
                ->select('model_id')
                ->where('model_type', 'App\\Models\\Resident')
                ->where('action', 'create'))
            ->update(['is_legacy_imported' => true]);
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropIndex(['is_legacy_imported']);
            $table->dropColumn('is_legacy_imported');
        });

        Schema::dropIfExists('civil_statuses');
        Schema::dropIfExists('educational_attainments');
    }
};
