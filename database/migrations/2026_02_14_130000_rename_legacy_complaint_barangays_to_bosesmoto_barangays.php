<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyTable = 'boss'.'moto_barangays';

        if (Schema::hasTable($legacyTable) && ! Schema::hasTable('bosesmoto_barangays')) {
            Schema::rename($legacyTable, 'bosesmoto_barangays');
        }
    }

    public function down(): void
    {
        $legacyTable = 'boss'.'moto_barangays';

        if (Schema::hasTable('bosesmoto_barangays') && ! Schema::hasTable($legacyTable)) {
            Schema::rename('bosesmoto_barangays', $legacyTable);
        }
    }
};
