<?php

namespace Tests\Feature;

use App\Livewire\Admin\CitizenServicesManager;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SosAlertMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_contains_all_open_and_acknowledged_alerts_with_coordinates(): void
    {
        $this->createAlert('SOS-OPEN', 'open', 16.1554, 119.9812);
        $this->createAlert('SOS-ACKNOWLEDGED', 'acknowledged', 16.1560, 119.9820);
        $this->createAlert('SOS-RESOLVED', 'resolved', 16.1570, 119.9830);
        $this->createAlert('SOS-NO-COORDINATES', 'open');

        Livewire::actingAs(User::factory()->create())
            ->test(CitizenServicesManager::class)
            ->set('activeTab', 'sos')
            ->assertSee('Active SOS Alert Map')
            ->assertViewHas('sosMapAlerts', function (array $alerts): bool {
                $references = collect($alerts)->pluck('reference');

                return count($alerts) === 2
                    && $references->contains('SOS-OPEN')
                    && $references->contains('SOS-ACKNOWLEDGED')
                    && ! $references->contains('SOS-RESOLVED')
                    && ! $references->contains('SOS-NO-COORDINATES');
            });
    }

    private function createAlert(
        string $reference,
        string $status,
        ?float $latitude = null,
        ?float $longitude = null
    ): SosAlert {
        return SosAlert::query()->create([
            'reference_number' => $reference,
            'status' => $status,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
