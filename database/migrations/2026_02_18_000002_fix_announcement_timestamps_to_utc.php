<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Announcements created while app timezone was Asia/Manila stored local
     * Manila times in published_at / expires_at.  Now that the app runs in
     * UTC we need to shift those values back 8 hours so they represent the
     * correct UTC instant.
     */
    public function up(): void
    {
        DB::table('announcements')
            ->whereNotNull('published_at')
            ->update([
                'published_at' => DB::raw("DATE_SUB(published_at, INTERVAL 8 HOUR)"),
            ]);

        DB::table('announcements')
            ->whereNotNull('expires_at')
            ->update([
                'expires_at' => DB::raw("DATE_SUB(expires_at, INTERVAL 8 HOUR)"),
            ]);
    }

    public function down(): void
    {
        DB::table('announcements')
            ->whereNotNull('published_at')
            ->update([
                'published_at' => DB::raw("DATE_ADD(published_at, INTERVAL 8 HOUR)"),
            ]);

        DB::table('announcements')
            ->whereNotNull('expires_at')
            ->update([
                'expires_at' => DB::raw("DATE_ADD(expires_at, INTERVAL 8 HOUR)"),
            ]);
    }
};
