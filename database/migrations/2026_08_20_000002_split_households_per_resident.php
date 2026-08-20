<?php

use App\Services\HouseholdResidentSplitService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(HouseholdResidentSplitService::class)->run();
    }

    public function down(): void
    {
        app(HouseholdResidentSplitService::class)->reverse();
    }
};
