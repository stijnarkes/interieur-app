<?php

namespace Tests\Feature;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de DB-afhankelijke regels uit QuizScoringService::compute() — de puur rekenkundige regels
 * (welke stijl wint, wanneer een invloed getoond wordt) staan apart getest in
 * tests/Unit/QuizScoringServiceTest.php.
 */
class QuizScoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuestion(string $key, int $weight = 1, int $maxSelections = 1): QuizQuestion
    {
        return QuizQuestion::create([
            'question_key' => $key,
            'section' => 'materials-colors',
            'title' => $key,
            'folder' => null,
            'sort_order' => 10,
            'max_selections' => $maxSelections,
            'weight' => $weight,
            'image_display_mode' => 'contain',
        ]);
    }

    private function makeOption(string $questionKey, string $slug, ?string $primaryStyle, ?string $secondaryStyle = null): QuizOption
    {
        return QuizOption::create([
            'question_id' => $questionKey,
            'style_key' => $primaryStyle ?? 'japandi',
            'option_slug' => $slug,
            'primary_style' => $primaryStyle,
            'secondary_style' => $secondaryStyle,
            'title' => $slug,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function een_optie_met_twee_stijlen_geeft_beide_de_volledige_punten(): void
    {
        $this->makeQuestion('vloer', weight: 1);
        $this->makeOption('vloer', 'eiken', 'japandi', 'natuurlijk');

        $computed = app(QuizScoringService::class)->compute(['vloer' => ['eiken']]);

        $this->assertSame(1.0, $computed['style_scores']['japandi']);
        $this->assertSame(1.0, $computed['style_scores']['natuurlijk']);
    }

    #[Test]
    public function een_of_twee_gekozen_opties_geven_hetzelfde_totaalgewicht(): void
    {
        $this->makeQuestion('stof', weight: 2, maxSelections: 2);
        $this->makeOption('stof', 'linnen', 'japandi');
        $this->makeOption('stof', 'bouclé', 'scandinavisch');

        $eenKeuze = app(QuizScoringService::class)->compute(['stof' => ['linnen']]);
        $this->assertSame(2.0, $eenKeuze['style_scores']['japandi']);

        $tweeKeuzes = app(QuizScoringService::class)->compute(['stof' => ['linnen', 'bouclé']]);
        $totaal = $tweeKeuzes['style_scores']['japandi'] + $tweeKeuzes['style_scores']['scandinavisch'];
        $this->assertSame(2.0, $totaal);
    }

    #[Test]
    public function zwaardere_vragen_tellen_harder_mee(): void
    {
        // Simuleert de bank/keuken/eethoek (gewicht 2) versus de overige vragen (gewicht 1).
        $this->makeQuestion('bank', weight: 2);
        $this->makeOption('bank', 'lage-bank', 'japandi');

        $this->makeQuestion('verlichting', weight: 1);
        $this->makeOption('verlichting', 'hanglamp', 'scandinavisch');

        $computed = app(QuizScoringService::class)->compute([
            'bank' => ['lage-bank'],
            'verlichting' => ['hanglamp'],
        ]);

        $this->assertGreaterThan($computed['style_scores']['scandinavisch'], $computed['style_scores']['japandi']);
        $this->assertSame('japandi', $computed['primary_style']);
    }

    #[Test]
    public function een_optie_zonder_hoofdstijl_draagt_geen_punten_bij(): void
    {
        $this->makeQuestion('kraan', weight: 1);
        $this->makeOption('kraan', 'onvolledig', null);

        $computed = app(QuizScoringService::class)->compute(['kraan' => ['onvolledig']]);

        $this->assertSame(0.0, array_sum($computed['style_scores']));
        $this->assertNull($computed['primary_style']);
    }

    #[Test]
    public function een_duidelijke_tweede_invloed_uit_twee_vragen_komt_door_in_het_eindresultaat(): void
    {
        $this->makeQuestion('vloer', weight: 1);
        $this->makeOption('vloer', 'eiken', 'japandi');

        $this->makeQuestion('bank', weight: 1);
        $this->makeOption('bank', 'lage-bank', 'japandi');

        $this->makeQuestion('kraan', weight: 1);
        $this->makeOption('kraan', 'kraan-a', 'scandinavisch');

        $this->makeQuestion('servies', weight: 1);
        $this->makeOption('servies', 'servies-a', 'scandinavisch');

        $computed = app(QuizScoringService::class)->compute([
            'vloer' => ['eiken'],
            'bank' => ['lage-bank'],
            'kraan' => ['kraan-a'],
            'servies' => ['servies-a'],
        ]);

        $this->assertSame('japandi', $computed['primary_style']);
        $this->assertSame('scandinavisch', $computed['secondary_style']);
    }
}
