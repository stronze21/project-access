<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
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
