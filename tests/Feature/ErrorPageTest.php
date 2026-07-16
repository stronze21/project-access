<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_missing_pages_use_the_branded_404_screen(): void
    {
        $this->get('/a-page-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('This page took a wrong turn')
            ->assertSee('Alaminos City ACCESS');
    }

    public function test_unsupported_methods_use_the_branded_405_screen(): void
    {
        $this->post('/mobile-app')
            ->assertStatus(405)
            ->assertSee('That action is not available here')
            ->assertSee('Alaminos City ACCESS');
    }
}
