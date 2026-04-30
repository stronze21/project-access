<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_deletion_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('account_deletion_requests', 'requested_action')) {
                $table->string('requested_action', 80)
                    ->default('delete-account-and-data')
                    ->after('reason');
            }

            if (!Schema::hasColumn('account_deletion_requests', 'retention_acknowledged')) {
                $table->boolean('retention_acknowledged')
                    ->default(false)
                    ->after('requested_action');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_deletion_requests', function (Blueprint $table) {
            if (Schema::hasColumn('account_deletion_requests', 'retention_acknowledged')) {
                $table->dropColumn('retention_acknowledged');
            }

            if (Schema::hasColumn('account_deletion_requests', 'requested_action')) {
                $table->dropColumn('requested_action');
            }
        });
    }
};
