<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResidentIdCardLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_id_card_uses_the_access_landscape_layout_and_existing_qr_route(): void
    {
        $resident = $this->createResident([
            'qr_code' => 'R-TEST-1',
            'signature' => 'data:image/png;base64,dGVzdA==',
        ]);

        $response = $this->withoutMiddleware()->get(route('residents.id-card.landscape', $resident));

        $response
            ->assertOk()
            ->assertSee('ACCESS ID')
            ->assertSee('Gender:')
            ->assertSee('DELA CRUZ')
            ->assertSee('JUAN')
            ->assertSee('R-202607-0001')
            ->assertSee(route('qrcode.resident', $resident), false)
            ->assertSee('images/id-cards/access-id-front.png', false)
            ->assertSee('images/id-cards/access-id-back.jpg', false)
            ->assertSee('ID Card Editor')
            ->assertSee('data-editor-key="qr-code"', false)
            ->assertSee('Reset Selected')
            ->assertSee('(075) 551 2146');

        $this->assertFileExists(public_path('images/id-cards/access-id-front.png'));
        $this->assertFileExists(public_path('images/id-cards/access-id-back.jpg'));
    }

    public function test_resident_qr_code_is_rendered_as_svg_without_imagick(): void
    {
        $resident = $this->createResident([
            'resident_id' => '16-04175',
            'qr_code' => 'R-EXISTING-QR-0099',
        ]);

        $response = $this->withoutMiddleware()->get(route('qrcode.resident', $resident));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);

        $this->assertSame('AC-16-04175', $resident->fresh()->qr_code);

        $scanResult = app(QrCodeService::class)->processQrCode('AC-16-04175');
        $this->assertTrue($scanResult['found']);
        $this->assertSame($resident->id, $scanResult['id']);

        $this->withoutMiddleware()
            ->get(route('qrcode.download', $resident))
            ->assertDownload('resident_qr_'.$resident->full_name.'.svg');
    }

    public function test_batch_id_cards_render_front_and_back_for_every_selected_resident(): void
    {
        $first = $this->createResident(['resident_id' => 'R-202607-0001']);
        $second = $this->createResident([
            'resident_id' => 'R-202607-0002',
            'first_name' => 'MARIA',
            'last_name' => 'SANTOS',
        ]);

        $response = $this->withoutMiddleware()->post(route('residents.id-cards.batch'), [
            'residents' => [$first->id, $second->id],
        ]);

        $response
            ->assertOk()
            ->assertSee('Print 2 ID Card(s)')
            ->assertSee('R-202607-0001')
            ->assertSee('R-202607-0002')
            ->assertSee('data-side="front"', false)
            ->assertSee('data-side="back"', false);

        $this->assertSame(2, substr_count($response->getContent(), 'data-side="front"'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-side="back"'));
    }

    public function test_portrait_id_card_support_is_removed(): void
    {
        $this->assertFalse(Route::has('residents.id-card.portrait'));
        $this->assertFileDoesNotExist(resource_path('views/residents/id-card-portrait.blade.php'));

        $batchForm = file_get_contents(resource_path('views/residents/id-card-batch-form.blade.php'));

        $this->assertStringNotContainsString('Portrait Format', $batchForm);
        $this->assertStringNotContainsString('name="orientation"', $batchForm);
        $this->assertStringContainsString('Landscape CR80 format', $batchForm);
    }

    private function createResident(array $overrides = []): Resident
    {
        return Resident::create(array_merge([
            'resident_id' => 'R-202607-0001',
            'qr_code' => null,
            'first_name' => 'JUAN',
            'last_name' => 'DELA CRUZ',
            'middle_name' => 'SANTOS',
            'birth_date' => '1990-01-30',
            'gender' => 'male',
            'is_active' => true,
        ], $overrides));
    }
}
