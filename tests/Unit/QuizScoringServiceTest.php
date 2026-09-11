<?php

namespace Tests\Unit;

use App\Services\QuizScoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test determineRanking() in isolatie (geen DB nodig — pure functie): dekt de 4 gevallen uit het
 * implementatieplan (duidelijke winnaar / twee bijna-gelijk / drie bijna-gelijk / tegenstrijdig)
 * met dezelfde marges als de quiz_settings-defaults (15 / 8 / 5).
 */
class QuizScoringServiceTest extends TestCase
{
    private QuizScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizScoringService;
    }

    #[Test]
    public function een_duidelijke_winnaar_wordt_herkend(): void
    {
        $result = $this->service->determineRanking(
            ['japandi' => 60, 'modern' => 20, 'natuurlijk' => 20],
            primaryDominantMargin: 15,
            closePairMargin: 8,
            closeTripleMargin: 5,
        );

        $this->assertSame('clear_winner', $result['case']);
        $this->assertSame('japandi', $result['primary_style']);
        $this->assertSame('strong', $result['primary_strength']);
        $this->assertSame('modern', $result['secondary_style']);
    }

    #[Test]
    public function twee_bijna_gelijke_stijlen_worden_als_gemengd_profiel_herkend(): void
    {
        $result = $this->service->determineRanking(
            ['japandi' => 42, 'scandinavisch' => 38, 'modern' => 20],
            primaryDominantMargin: 15,
            closePairMargin: 8,
            closeTripleMargin: 5,
        );

        $this->assertSame('close_pair', $result['case']);
        $this->assertSame('japandi', $result['primary_style']);
        $this->assertSame('scandinavisch', $result['secondary_style']);
        $this->assertSame('strong', $result['primary_strength']);
        $this->assertSame('strong', $result['secondary_strength']);
    }

    #[Test]
    public function drie_bijna_gelijke_stijlen_worden_als_drieweg_mix_herkend(): void
    {
        $result = $this->service->determineRanking(
            ['japandi' => 36, 'scandinavisch' => 33, 'natuurlijk' => 31],
            primaryDominantMargin: 15,
            closePairMargin: 8,
            closeTripleMargin: 5,
        );

        $this->assertSame('close_triple', $result['case']);
        $this->assertSame('moderate', $result['primary_strength']);
        $this->assertSame('moderate', $result['secondary_strength']);
        $this->assertSame('moderate', $result['tertiary_strength']);
    }

    #[Test]
    public function een_onduidelijke_tussenvorm_wordt_als_tegenstrijdig_gemarkeerd(): void
    {
        // gap1 (10) zit tussen close_pair_margin (8) en primary_dominant_margin (15) in — te
        // groot voor "twee bijna-gelijk", te klein voor "duidelijke winnaar".
        $result = $this->service->determineRanking(
            ['japandi' => 50, 'scandinavisch' => 40, 'modern' => 10],
            primaryDominantMargin: 15,
            closePairMargin: 8,
            closeTripleMargin: 5,
        );

        $this->assertSame('contradictory', $result['case']);
        $this->assertSame('japandi', $result['primary_style']);
    }

    #[Test]
    public function geen_enkele_score_levert_geen_stijlen_op(): void
    {
        $result = $this->service->determineRanking(
            ['japandi' => 0, 'modern' => 0],
            primaryDominantMargin: 15,
            closePairMargin: 8,
            closeTripleMargin: 5,
        );

        $this->assertNull($result['primary_style']);
        $this->assertNull($result['secondary_style']);
    }
}
