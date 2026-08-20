<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'residents_household_id_unique';

    public function up(): void
    {
        $hasUnassignedResidents = DB::table('residents')->whereNull('household_id')->exists();
        $hasSharedHouseholds = DB::table('residents')
            ->select('household_id')
            ->whereNotNull('household_id')
            ->groupBy('household_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasUnassignedResidents || $hasSharedHouseholds) {
            throw new \RuntimeException(
                'Household conversion is incomplete; refusing to add the residents.household_id unique constraint.'
            );
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->unique('household_id', self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }
};
