<?php

namespace Tests\Unit;

use App\Models\QuizOption;
use App\Models\StyleCombinationAdvice;
use App\Services\PartnerComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de deterministische vergelijkingsregels uit het implementatieplan, sectie 6: alleen
 * feitelijke overeenkomsten/verschillen, geen matchpercentage, geen verzonnen kleurfamilie-/
 * materiaalclaims zonder beheerde data.
 */
class PartnerComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartnerComparisonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PartnerComparisonService;
    }

    #[Test]
    public function gelijke_primaire_stijl_levert_een_overeenkomst_op_geen_verschil(): void
    {
        $result = $this->service->compare(
            ['primary_style' => 'japandi', 'answers' => []],
            ['primary_style' => 'japandi', 'answers' => []],
        );

        $this->assertSame(
            [['type' => 'primary_style_match', 'styleKey' => 'japandi']],
            $result['facts']['similarities']
        );
        $this->assertSame([], $result['facts']['differences']);
    }

    #[Test]
    public function verschillende_primaire_stijl_levert_een_verschil_op_geen_overeenkomst(): void
    {
        $result = $this->service->compare(
            ['primary_style' => 'japandi', 'answers' => []],
            ['primary_style' => 'modern', 'answers' => []],
        );

        $this->assertSame([], $result['facts']['similarities']);
        $this->assertSame(
            [['type' => 'primary_style_difference', 'initiatorStyleKey' => 'japandi', 'partnerStyleKey' => 'modern']],
            $result['facts']['differences']
        );
    }

    #[Test]
    public function een_gedeelde_secundaire_stijl_telt_als_overeenkomst(): void
    {
        $result = $this->service->compare(
            ['primary_style' => 'japandi', 'secondary_style' => 'scandinavisch', 'answers' => []],
            ['primary_style' => 'modern', 'secondary_style' => 'scandinavisch', 'answers' => []],
        );

        $this->assertContains(
            ['type' => 'secondary_style_match', 'styleKey' => 'scandinavisch'],
            $result['facts']['similarities']
        );
    }

    #[Test]
    public function exact_dezelfde_basispaletkleur_telt_als_overeenkomst_kleurfamilies_worden_niet_verzonnen(): void
    {
        $result = $this->service->compare(
            ['answers' => [], 'chosen_base_palette' => ['colors' => [['name' => 'Ivoor', 'hex' => '#F3EEE3']]]],
            ['answers' => [], 'chosen_base_palette' => ['colors' => [['name' => 'Ivoor', 'hex' => '#f3eee3']]]],
        );

        $this->assertContains(
            ['type' => 'base_palette_color_match', 'hexes' => ['#f3eee3']],
            $result['facts']['similarities']
        );
    }

    #[Test]
    public function exact_dezelfde_accentkleur_telt_als_overeenkomst(): void
    {
        $result = $this->service->compare(
            ['answers' => [], 'chosen_accent_colors' => [['name' => 'Terracotta', 'hex' => '#B5603C']]],
            ['answers' => [], 'chosen_accent_colors' => [['name' => 'Terracotta', 'hex' => '#B5603C']]],
        );

        $this->assertContains(
            ['type' => 'accent_color_match', 'hexes' => ['#b5603c']],
            $result['facts']['similarities']
        );
    }

    #[Test]
    public function verschillende_accentkleuren_leveren_geen_kleurovereenkomst_op(): void
    {
        $result = $this->service->compare(
            ['answers' => [], 'chosen_accent_colors' => [['name' => 'Terracotta', 'hex' => '#B5603C']]],
            ['answers' => [], 'chosen_accent_colors' => [['name' => 'Salie', 'hex' => '#8A9A8B']]],
        );

        $types = array_column($result['facts']['similarities'], 'type');
        $this->assertNotContains('accent_color_match', $types);
    }

    #[Test]
    public function gedeeld_gekozen_beeld_telt_als_overeenkomst(): void
    {
        $result = $this->service->compare(
            ['answers' => ['vloer' => ['eiken', 'beton']]],
            ['answers' => ['vloer' => ['eiken']]],
        );

        $this->assertContains(
            ['type' => 'shared_option_selection', 'optionSlugs' => ['eiken']],
            $result['facts']['similarities']
        );
    }

    #[Test]
    public function materiaaltags_zonder_beheerde_data_leveren_nooit_een_verzonnen_claim_op(): void
    {
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
            'tags' => null,
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'modern', 'option_slug' => 'beton',
            'primary_style' => 'modern', 'title' => 'Betonlook vloer', 'is_active' => true, 'has_image' => false,
            'tags' => null,
        ]);

        $result = $this->service->compare(
            ['answers' => ['vloer' => ['eiken']]],
            ['answers' => ['vloer' => ['beton']]],
        );

        $types = array_column($result['facts']['similarities'], 'type');
        $this->assertNotContains('shared_material_tags', $types);
    }

    #[Test]
    public function gedeelde_beheerde_materiaaltags_tellen_wel_als_overeenkomst(): void
    {
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
            'tags' => ['hout', 'natuurlijk'],
        ]);
        QuizOption::create([
            'question_id' => 'wand', 'style_key' => 'modern', 'option_slug' => 'eiken-paneel',
            'primary_style' => 'modern', 'title' => 'Eiken wandpaneel', 'is_active' => true, 'has_image' => false,
            'tags' => ['hout'],
        ]);

        $result = $this->service->compare(
            ['answers' => ['vloer' => ['eiken']]],
            ['answers' => ['wand' => ['eiken-paneel']]],
        );

        $this->assertContains(
            ['type' => 'shared_material_tags', 'tags' => ['hout']],
            $result['facts']['similarities']
        );
    }

    #[Test]
    public function een_gepubliceerd_stijladvies_wordt_gebruikt_als_suggestie(): void
    {
        StyleCombinationAdvice::create([
            'style_key_a' => 'japandi', 'style_key_b' => 'modern',
            'title' => 'Rustig en strak', 'intro' => 'Een mooie combinatie.',
            'basis_tip' => 'Basis-tip', 'materials_tip' => 'Materialen-tip', 'accent_tip' => 'Accent-tip',
            'status' => 'published', 'version' => 3,
        ]);

        $result = $this->service->compare(
            ['primary_style' => 'modern', 'answers' => []],
            ['primary_style' => 'japandi', 'answers' => []],
        );

        $this->assertSame('editorial', $result['suggestions']['source']);
        $this->assertSame('Rustig en strak', $result['suggestions']['title']);
        $this->assertSame('3', $result['contentVersion']);
    }

    #[Test]
    public function een_conceptadvies_of_ontbrekend_advies_valt_terug_op_de_vaste_fallbacktekst(): void
    {
        StyleCombinationAdvice::create([
            'style_key_a' => 'japandi', 'style_key_b' => 'modern',
            'title' => 'Rustig en strak', 'intro' => 'Een mooie combinatie.',
            'basis_tip' => 'Basis-tip', 'materials_tip' => 'Materialen-tip', 'accent_tip' => 'Accent-tip',
            'status' => 'concept', 'version' => 1,
        ]);

        $result = $this->service->compare(
            ['primary_style' => 'modern', 'answers' => []],
            ['primary_style' => 'japandi', 'answers' => []],
        );

        $this->assertSame('fallback', $result['suggestions']['source']);
        $this->assertSame('fallback', $result['contentVersion']);
    }

    #[Test]
    public function de_volgorde_van_de_stijlen_maakt_niet_uit_voor_het_gekozen_advies(): void
    {
        StyleCombinationAdvice::create([
            'style_key_a' => 'japandi', 'style_key_b' => 'modern',
            'title' => 'Rustig en strak', 'intro' => 'Een mooie combinatie.',
            'basis_tip' => 'Basis-tip', 'materials_tip' => 'Materialen-tip', 'accent_tip' => 'Accent-tip',
            'status' => 'published', 'version' => 1,
        ]);

        $ab = $this->service->compare(
            ['primary_style' => 'japandi', 'answers' => []],
            ['primary_style' => 'modern', 'answers' => []],
        );
        $ba = $this->service->compare(
            ['primary_style' => 'modern', 'answers' => []],
            ['primary_style' => 'japandi', 'answers' => []],
        );

        $this->assertSame($ab['suggestions'], $ba['suggestions']);
    }

    #[Test]
    public function er_worden_nooit_meer_dan_drie_overeenkomsten_of_verschillen_getoond(): void
    {
        $result = $this->service->compare(
            [
                'primary_style' => 'japandi',
                'secondary_style' => 'scandinavisch',
                'answers' => ['a' => ['1', '2'], 'b' => ['3']],
                'chosen_base_palette' => ['colors' => [['name' => 'Ivoor', 'hex' => '#F3EEE3']]],
                'chosen_accent_colors' => [['name' => 'Terracotta', 'hex' => '#B5603C']],
            ],
            [
                'primary_style' => 'japandi',
                'secondary_style' => 'scandinavisch',
                'answers' => ['a' => ['1', '2'], 'b' => ['3']],
                'chosen_base_palette' => ['colors' => [['name' => 'Ivoor', 'hex' => '#F3EEE3']]],
                'chosen_accent_colors' => [['name' => 'Terracotta', 'hex' => '#B5603C']],
            ],
        );

        $this->assertLessThanOrEqual(3, count($result['facts']['similarities']));
    }
}
