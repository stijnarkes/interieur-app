<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt dat de gezamenlijke PDF ieders eigen invloed als los "Invloed: ..."-label toont naast de
 * hoofdstijl (zie PartnerReportPdfService::generate()) — voorheen kwam een invloed/secundaire
 * stijl nergens in de gezamenlijke uitslag voor. Rendert de blade-view rechtstreeks (zelfde
 * aanpak als QuizResultPdfDescriptionTest), los van de daadwerkelijke partnerflow.
 */
class PartnerResultPdfSecondaryStyleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function baseData(array $overrides = []): array
    {
        return array_replace([
            'initiatorName' => 'Stijn',
            'partnerName' => 'Anna',
            'initiatorStyleLabel' => 'Modern Scandinavisch',
            'partnerStyleLabel' => 'Landelijk',
            'initiatorSecondaryStyleLabel' => null,
            'partnerSecondaryStyleLabel' => null,
            'initiatorPalette' => [],
            'partnerPalette' => [],
            'initiatorAccentColors' => [],
            'partnerAccentColors' => [],
            'similarities' => [],
            'differences' => [],
            'suggestions' => [],
            'ctaLabel' => null,
            'ctaUrl' => null,
        ], $overrides);
    }

    #[Test]
    public function zonder_invloed_toont_de_pdf_geen_invloed_label(): void
    {
        $html = view('pdf.partner-result', $this->baseData())->render();

        $this->assertStringNotContainsString('Invloed:', $html);
    }

    #[Test]
    public function met_invloed_bij_beide_toont_de_pdf_ieders_eigen_invloed_apart(): void
    {
        $html = view('pdf.partner-result', $this->baseData([
            'initiatorSecondaryStyleLabel' => 'Japandi',
            'partnerSecondaryStyleLabel' => 'Hotel luxe',
        ]))->render();

        $this->assertStringContainsString('Invloed: Japandi', $html);
        $this->assertStringContainsString('Invloed: Hotel luxe', $html);
    }

    #[Test]
    public function met_invloed_bij_maar_een_van_de_twee_toont_de_pdf_alleen_dat_ene_label(): void
    {
        $html = view('pdf.partner-result', $this->baseData([
            'initiatorSecondaryStyleLabel' => 'Japandi',
        ]))->render();

        $this->assertStringContainsString('Invloed: Japandi', $html);
        $this->assertSame(1, substr_count($html, 'Invloed:'));
    }
}
