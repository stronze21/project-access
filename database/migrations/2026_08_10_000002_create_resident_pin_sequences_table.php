<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_pin_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
        });

        $maximum = DB::table('residents')
            ->pluck('resident_id')
            ->reduce(function (int $current, string $pin): int {
                return preg_match('/^\d{2}-(\d{5})$/', $pin, $matches)
                    ? max($current, (int) $matches[1])
                    : $current;
            }, 0);

        DB::table('resident_pin_sequences')->insert([
            'id' => 1,
            'last_sequence' => $maximum,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_pin_sequences');
    }
};
