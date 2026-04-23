<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('households', function (Blueprint $table) {
            // Add new PSGC code fields
            $table->string('region_code')->nullable()->after('region');
            $table->string('province_code')->nullable()->after('province');
            $table->string('city_municipality_code')->nullable()->after('city_municipality');
            $table->string('barangay_code')->nullable()->after('barangay');
        });
    }

    public function down()
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropColumn([
                'region_code',
                'province_code',
                'city_municipality_code',
                'barangay_code'
            ]);
        });
    }
};