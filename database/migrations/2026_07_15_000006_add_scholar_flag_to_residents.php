<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('residents', 'is_scholar')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->boolean('is_scholar')->default(false)->after('is_4ps')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('residents', 'is_scholar')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->dropIndex(['is_scholar']);
                $table->dropColumn('is_scholar');
            });
        }
    }
};
