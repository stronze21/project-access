<?php

namespace Tests\Feature;

use App\Exceptions\BhwisConnectionException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BhwisUnavailableErrorPageTest extends TestCase
{
    public function test_web_requests_receive_the_bhwis_downtime_page(): void
    {
        Route::get('/testing/bhwis-unavailable', function () {
            throw new BhwisConnectionException('Sensitive connection detail.');
        });

        $this->get('/testing/bhwis-unavailable')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('The local BHWIS server is offline')
            ->assertSee('Try BHWIS again')
            ->assertDontSee('Sensitive connection detail.');
    }

    public function test_json_requests_receive_a_safe_retryable_response(): void
    {
        Route::get('/testing/bhwis-unavailable-json', function () {
            throw new BhwisConnectionException('Sensitive connection detail.');
        });

        $this->getJson('/testing/bhwis-unavailable-json')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertJson([
                'message' => 'The BHWIS local server is temporarily unavailable. Please try again later.',
                'error' => 'bhwis_unavailable',
                'retryable' => true,
            ])
            ->assertJsonMissing(['message' => 'Sensitive connection detail.']);
    }
}
