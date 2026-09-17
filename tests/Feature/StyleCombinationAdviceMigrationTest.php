<?php

namespace Tests\Feature;

use App\Models\StyleCombinationAdvice;
use App\Services\PartnerComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regressie: de 21 redactionele stijlcombinatie-adviezen stonden alleen in een los `db:seed`-
 * commando, dat nooit automatisch op productie draait — daardoor viel elke combinatie daar terug
 * op de generieke "Jullie combinatietip volgt nog"-tekst (zie PartnerComparisonService::
 * suggestionsFor()), ook al was de content allang geschreven. De migratie
 * 2026_09_17_090000_seed_style_combination_advices lost dit op door de content bij elke
 * `php artisan migrate` te (her)zetten. Deze test draait bewust zonder de tabel eerst leeg te
 * maken (in tegenstelling tot PartnerComparisonServiceTest/StyleCombinationAdvicesPageTest), juist
 * om te bevestigen wat een verse, gemigreerde database daadwerkelijk bevat.
 */
class StyleCombinationAdviceMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function een_verse_migratie_zet_alle_21_gepubliceerde_combinaties_neer(): void
    {
        $this->assertSame(21, StyleCombinationAdvice::count());
        $this->assertSame(21, StyleCombinationAdvice::where('status', 'published')->count());
    }

    /** Het exacte, door de gebruiker gemelde geval: hotel luxe + scandinavisch toonde de fallbacktekst i.p.v. de geschreven combinatietip. */
    #[Test]
    public function hotel_luxe_en_scandinavisch_krijgt_de_echte_tekst_niet_de_fallback(): void
    {
        $service = new PartnerComparisonService;

        $result = $service->compare(
            ['primary_style' => 'scandinavisch', 'answers' => []],
            ['primary_style' => 'hotelLuxe', 'answers' => []],
        );

        $this->assertSame('editorial', $result['suggestions']['source']);
        $this->assertSame('Hotel luxe & Scandinavisch', $result['suggestions']['title']);
        $this->assertNotSame('Jullie combinatietip volgt nog', $result['suggestions']['title']);
    }
}
