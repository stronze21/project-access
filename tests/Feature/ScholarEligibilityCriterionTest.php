<?php

namespace Tests\Feature;

use App\Livewire\AyudaProgramCreate;
use App\Models\EligibilityCriteria;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class ScholarEligibilityCriterionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scholar_criterion_uses_the_resident_scholar_flag(): void
    {
        $criterion = new EligibilityCriteria([
            'criterion_type' => 'scholar',
            'operator' => 'equals',
            'value' => 'true',
            'is_required' => true,
        ]);

        $this->assertTrue($criterion->checkEligibility(new Resident(['is_scholar' => true])));
        $this->assertFalse($criterion->checkEligibility(new Resident(['is_scholar' => false])));
    }

    public function test_scholar_criterion_only_accepts_boolean_values_and_equality_operators(): void
    {
        Livewire::test(AyudaProgramCreate::class)
            ->set('name', 'Scholar Assistance')
            ->set('criteria.0.name', 'Must be a scholar')
            ->set('criteria.0.type', 'scholar')
            ->set('criteria.0.operator', 'greater_than')
            ->set('criteria.0.value', 'sometimes')
            ->call('save')
            ->assertHasErrors([
                'criteria.0.operator',
                'criteria.0.value',
            ]);
    }

    public function test_selecting_scholar_initializes_a_valid_flag_criterion(): void
    {
        Livewire::test(AyudaProgramCreate::class)
            ->set('criteria.0.type', 'scholar')
            ->assertSet('criteria.0.operator', 'equals')
            ->assertSet('criteria.0.value', 'true');
    }

    public function test_api_rejects_an_invalid_scholar_criterion(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/programs', [
            'name' => 'Scholar Assistance',
            'type' => 'cash',
            'start_date' => now()->toDateString(),
            'eligibility_criteria' => [[
                'criterion_name' => 'Must be a scholar',
                'criterion_type' => 'scholar',
                'operator' => 'greater_than',
                'value' => 'sometimes',
                'is_required' => true,
            ]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'eligibility_criteria.0.operator',
                'eligibility_criteria.0.value',
            ]);
    }
}
