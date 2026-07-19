<?php

namespace Tests\Feature;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\Resident;
use Database\Seeders\ResidentThreeAyudaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentThreeAyudaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_three_programs_and_distributions_for_resident_three(): void
    {
        foreach (range(1, 3) as $number) {
            Resident::query()->create([
                'resident_id' => "R-AYUDA-SEED-{$number}",
                'first_name' => "Resident {$number}",
                'last_name' => 'Demo',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'civil_status' => 'single',
                'is_active' => true,
            ]);
        }

        $this->seed(ResidentThreeAyudaSeeder::class);
        $this->seed(ResidentThreeAyudaSeeder::class);

        $this->assertDatabaseCount('ayuda_programs', 3);
        $this->assertDatabaseCount('distributions', 3);
        $this->assertSame([3], Distribution::query()->distinct()->pluck('resident_id')->all());
        $this->assertSame(
            ['distributed', 'pending', 'verified'],
            Distribution::query()->orderBy('status')->pluck('status')->all()
        );
        $this->assertSame(3, AyudaProgram::query()->where('is_active', true)->count());
    }
}
