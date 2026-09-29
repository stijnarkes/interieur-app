<?php

namespace Tests\Unit;

use App\Services\QuizScoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test determineResult() in isolatie (geen DB nodig — pure functie) — zie de opdracht "scoring
 * woonstijltest Boer Staphorst". De DB-afhankelijke regels (matrixscores per optie, vraaggewicht,
 * gemiddelde/spreiding per vraag) staan in tests/Feature/QuizScoringWoonstijlTest.php.
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
    public function een_te_groot_verschil_met_de_hoofdstijl_krijgt_geen_invloed(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 3.0, 'scandinavisch' => 1.0],
            abovePerQuestionAverageCount: ['japandi' => 3, 'scandinavisch' => 2],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertNull($result['secondary']);
    }

    #[Test]
    public function een_tweede_stijl_binnen_de_marge_positief_en_op_genoeg_vragen_krijgt_invloed(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 2.0, 'scandinavisch' => 1.7],
            abovePerQuestionAverageCount: ['japandi' => 4, 'scandinavisch' => 2],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertSame('scandinavisch', $result['secondary']);
    }

    #[Test]
    public function een_tweede_stijl_binnen_de_marge_maar_niet_positief_krijgt_geen_invloed(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 0.2, 'scandinavisch' => -0.1],
            abovePerQuestionAverageCount: ['japandi' => 3, 'scandinavisch' => 3],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertNull($result['secondary'], 'Een niet-positieve uitslagScore mag nooit als invloed getoond worden, ongeacht de marge.');
    }

    #[Test]
    public function een_tweede_stijl_op_te_weinig_vragen_boven_gemiddelde_krijgt_geen_invloed(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 2.0, 'scandinavisch' => 1.8],
            abovePerQuestionAverageCount: ['japandi' => 4, 'scandinavisch' => 1],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertNull($result['secondary'], 'Minder dan 2 vragen boven het eigen vraaggemiddelde mag nooit als invloed getoond worden.');
    }

    #[Test]
    public function nooit_een_derde_stijl(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 2.0, 'scandinavisch' => 1.9, 'modern' => 1.8],
            abovePerQuestionAverageCount: ['japandi' => 4, 'scandinavisch' => 3, 'modern' => 3],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertContains($result['secondary'], ['scandinavisch', null]);
        $this->assertNotSame('modern', $result['secondary']);
    }

    #[Test]
    public function bij_exact_gelijke_hoogste_uitslagscore_worden_beide_stijlen_getoond(): void
    {
        // Exacte tie aan de top: geen van de drie invloed-eisen (positief/marge/spreiding) geldt
        // hier nog voor — zie de opdracht "een exact gelijke hoogste uitslag mag als twee stijlen
        // worden getoond". Bewust op 0 vragen boven gemiddelde en een negatieve score gezet, om te
        // bevestigen dat die eisen hier terecht genegeerd worden.
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => -0.4, 'scandinavisch' => -0.4],
            abovePerQuestionAverageCount: ['japandi' => 0, 'scandinavisch' => 0],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
        $this->assertSame('scandinavisch', $result['secondary']);
    }

    #[Test]
    public function bij_gelijke_hoogste_score_wint_de_eerst_gedeclareerde_stijl_als_hoofdstijl(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['scandinavisch' => 1.0, 'japandi' => 1.0],
            abovePerQuestionAverageCount: ['scandinavisch' => 2, 'japandi' => 2],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('scandinavisch', $result['primary']);
        $this->assertSame('japandi', $result['secondary']);
    }

    #[Test]
    public function een_negatieve_hoogste_uitslagscore_wordt_alsnog_als_hoofdstijl_gekozen(): void
    {
        // Anders dan de oude ruwe-score-uitslag (die een primaire score > 0 vereiste): de
        // uitslagScore is een genormaliseerd verschil met het verwachte gemiddelde en mag negatief
        // zijn — de minst-negatieve/hoogste stijl wint nog steeds, zolang er iets beantwoord is.
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => -0.2, 'modern' => -1.5],
            abovePerQuestionAverageCount: ['japandi' => 1, 'modern' => 0],
            answeredQuestionCount: 9,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertSame('japandi', $result['primary']);
    }

    #[Test]
    public function geen_beantwoorde_vragen_levert_geen_stijlen_op(): void
    {
        $result = $this->service->determineResult(
            uitslagScores: ['japandi' => 5.0, 'modern' => 3.0],
            abovePerQuestionAverageCount: ['japandi' => 0, 'modern' => 0],
            answeredQuestionCount: 0,
            secondaryInfluenceMaxGap: 0.5,
        );

        $this->assertNull($result['primary']);
        $this->assertNull($result['secondary']);
    }
}
