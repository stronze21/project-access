<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_resident_activation_uses_scroll_gated_legal_modals(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 15; Mobile) AppleWebKit/537.36')
            ->get(route('resident-portal.register'))
            ->assertOk()
            ->assertSee('data-activation-form', false)
            ->assertSee('data-legal-dialog="terms"', false)
            ->assertSee('data-legal-dialog="privacy"', false)
            ->assertSee('data-legal-dialog="consent"', false)
            ->assertSee('data-legal-checkbox="terms" required disabled', false)
            ->assertSee('data-legal-checkbox="privacy" required disabled', false)
            ->assertSee('data-legal-checkbox="consent" required disabled', false)
            ->assertSee('data-activation-submit disabled', false);
    }

    public function test_privacy_page_uses_the_official_privacy_notice_and_consent(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Notice and Consent')
            ->assertSee('The City Government of Alaminos, Pangasinan')
            ->assertSee('English')
            ->assertSee('Filipino')
            ->assertSee('RA 7160')
            ->assertSee('Data Privacy Act of 2012')
            ->assertSee('Barangay Health Worker Information System (BHWIS)')
            ->assertSee('Sumasang-ayon')
            ->assertSee('Di Sumasang-ayon');
    }
}
