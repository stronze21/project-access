<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordAndThemeUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_password_reveal_and_theme_toggle(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('data-password-toggle', false);
        $response->assertSee('project-access-theme', false);
        $response->assertSee('Switch to dark mode', false);
        $response->assertSee('Mobile App');
        $response->assertSee('href="'.route('mobile-app.index').'"', false);
    }

    public function test_password_reset_page_renders_reveal_controls_for_both_password_fields(): void
    {
        $response = $this->get('/reset-password/test-token?email=test@example.com');

        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'data-password-toggle'));
    }
}
