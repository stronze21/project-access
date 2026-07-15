<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('residents', 'is_bhw')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->boolean('is_bhw')->default(false)->after('is_4ps')->index();
            });
        }

        $duplicateSlots = DB::table('barangay_health_worker_assignments')
            ->select('barangay_zone_id', 'assignment_slot', DB::raw('MAX(id) AS keep_id'))
            ->groupBy('barangay_zone_id', 'assignment_slot')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        foreach ($duplicateSlots as $duplicate) {
            DB::table('barangay_health_worker_assignments')
                ->where('barangay_zone_id', $duplicate->barangay_zone_id)
                ->where('assignment_slot', $duplicate->assignment_slot)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        $duplicatePins = DB::table('barangay_health_worker_assignments')
            ->select('barangay_zone_id', 'legacy_pin')
            ->groupBy('barangay_zone_id', 'legacy_pin')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        foreach ($duplicatePins as $duplicate) {
            $keepId = DB::table('barangay_health_worker_assignments')
                ->where('barangay_zone_id', $duplicate->barangay_zone_id)
                ->where('legacy_pin', $duplicate->legacy_pin)
                ->orderByRaw("CASE WHEN assignment_slot = 'primary' THEN 0 ELSE 1 END")
                ->value('id');
            DB::table('barangay_health_worker_assignments')
                ->where('barangay_zone_id', $duplicate->barangay_zone_id)
                ->where('legacy_pin', $duplicate->legacy_pin)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        $indexes = $this->indexNames();
        if (! in_array('bhw_zone_foreign_index', $indexes, true)) {
            Schema::table('barangay_health_worker_assignments', function (Blueprint $table) {
                // MySQL may use the old composite unique index for this foreign key.
                $table->index('barangay_zone_id', 'bhw_zone_foreign_index');
            });
        }

        $indexes = $this->indexNames();
        Schema::table('barangay_health_worker_assignments', function (Blueprint $table) use ($indexes) {
            if (in_array('bhw_zone_pin_slot_unique', $indexes, true)) {
                $table->dropUnique('bhw_zone_pin_slot_unique');
            }
            if (! in_array('bhw_zone_slot_unique', $indexes, true)) {
                $table->unique(['barangay_zone_id', 'assignment_slot'], 'bhw_zone_slot_unique');
            }
            if (! in_array('bhw_zone_pin_unique', $indexes, true)) {
                $table->unique(['barangay_zone_id', 'legacy_pin'], 'bhw_zone_pin_unique');
            }
        });

        DB::table('residents')
            ->whereIn('id', DB::table('barangay_health_worker_assignments')
                ->select('resident_id')
                ->whereNotNull('resident_id'))
            ->update(['is_bhw' => true]);
    }

    public function down(): void
    {
        $indexes = $this->indexNames();
        Schema::table('barangay_health_worker_assignments', function (Blueprint $table) use ($indexes) {
            if (in_array('bhw_zone_slot_unique', $indexes, true)) {
                $table->dropUnique('bhw_zone_slot_unique');
            }
            if (in_array('bhw_zone_pin_unique', $indexes, true)) {
                $table->dropUnique('bhw_zone_pin_unique');
            }
            if (! in_array('bhw_zone_pin_slot_unique', $indexes, true)) {
                $table->unique(
                    ['barangay_zone_id', 'legacy_pin', 'assignment_slot'],
                    'bhw_zone_pin_slot_unique'
                );
            }
        });

        if (in_array('bhw_zone_foreign_index', $this->indexNames(), true)) {
            Schema::table('barangay_health_worker_assignments', function (Blueprint $table) {
                $table->dropIndex('bhw_zone_foreign_index');
            });
        }

        if (Schema::hasColumn('residents', 'is_bhw')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->dropIndex(['is_bhw']);
                $table->dropColumn('is_bhw');
            });
        }
    }

    private function indexNames(): array
    {
        return collect(Schema::getIndexes('barangay_health_worker_assignments'))
            ->pluck('name')
            ->all();
    }
};
