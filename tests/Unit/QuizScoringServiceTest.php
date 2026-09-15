<?php

namespace Tests\Unit;

use App\Services\QuizScoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test determineResult() in isolatie (geen DB nodig — pure functie). De DB-afhankelijke regels
 * (gewogen scoring per vraag/optie) staan in tests/Feature/QuizScoringIntegrationTest.php.
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
    public function een_duidelijke_winnaar_krijgt_geen_invloed(): void
    {
        // 20 vs 5 haalt de 70%-drempel bij lange na niet, dus geen invloed — ook al kreeg
        // scandinavisch in 2 vragen punten.
        $result = $this->service->determineResult(
            ['japandi' => 20.0, 'scandinavisch' => 5.0],
            ['japandi' => 3, 'scandinavisch' => 2],
            secondaryInfluenceRatio: 70,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertNull($result['secondary']);
    }

    #[Test]
    public function een_sterke_tweede_stijl_uit_twee_vragen_wordt_als_invloed_getoond(): void
    {
        $result = $this->service->determineResult(
            ['japandi' => 10.0, 'scandinavisch' => 8.0],
            ['japandi' => 3, 'scandinavisch' => 2],
            secondaryInfluenceRatio: 70,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertSame('scandinavisch', $result['secondary']);
    }

    #[Test]
    public function een_sterke_tweede_stijl_uit_slechts_een_vraag_krijgt_geen_invloed(): void
    {
        // Score-ratio is ruim voldoende (8/10 = 80% >= 70%), maar de stijl kwam maar uit 1 vraag.
        $result = $this->service->determineResult(
            ['japandi' => 10.0, 'scandinavisch' => 8.0],
            ['japandi' => 3, 'scandinavisch' => 1],
            secondaryInfluenceRatio: 70,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertNull($result['secondary']);
    }

    #[Test]
    public function nooit_een_derde_stijl(): void
    {
        $result = $this->service->determineResult(
            ['japandi' => 10.0, 'scandinavisch' => 9.0, 'natuurlijk' => 9.0],
            ['japandi' => 3, 'scandinavisch' => 2, 'natuurlijk' => 2],
            secondaryInfluenceRatio: 70,
        );

        $this->assertSame('japandi', $result['primary']);
        // Slechts één invloed mag getoond worden, ook al zou natuurlijk óók kwalificeren.
        $this->assertContains($result['secondary'], ['scandinavisch', null]);
        $this->assertNotSame('natuurlijk', $result['secondary']);
    }

    #[Test]
    public function bij_gelijke_scores_wint_de_stijl_die_eerst_in_de_array_staat(): void
    {
        // determineResult() is een pure functie: de tie-break is "eerst in de meegegeven array
        // wint" — QuizScoringService::compute() geeft die array altijd door in
        // QuizStructure::styleKeys()-volgorde, dus in de praktijk is dát de vaste volgorde.
        $result = $this->service->determineResult(
            ['scandinavisch' => 10.0, 'japandi' => 10.0],
            ['scandinavisch' => 2, 'japandi' => 2],
            secondaryInfluenceRatio: 70,
        );

        $this->assertSame('scandinavisch', $result['primary']);
    }

    #[Test]
    public function geen_enkele_score_levert_geen_stijlen_op(): void
    {
        $result = $this->service->determineResult(
            ['japandi' => 0.0, 'modern' => 0.0],
            ['japandi' => 0, 'modern' => 0],
            secondaryInfluenceRatio: 70,
        );

        $this->assertNull($result['primary']);
        $this->assertNull($result['secondary']);
    }
}
