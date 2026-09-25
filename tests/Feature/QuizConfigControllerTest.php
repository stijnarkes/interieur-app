<?php

namespace Tests\Feature;

use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizTransitionSection;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt dat de admin-beheerde overgangsscherm- en resultatenpagina-teksten (zie TekstenPage) ook
 * daadwerkelijk in de publieke /api/quiz-config-respons terechtkomen — dat is de enige weg
 * waarlangs remoteConfig.js ze in de klant-quiz kan overschrijven.
 */
class QuizConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function de_config_bevat_de_admin_bewerkte_overgangsscherm_en_resultaatteksten(): void
    {
        QuizTransitionSection::create([
            'section_id' => 'materials-colors',
            'title' => 'Test-titel',
            'tagline' => 'Test-tagline',
            'wrap_up' => null,
            'cta' => 'Test-cta',
        ]);

        SiteContent::current()->update([
            'hero_eyebrow' => 'Test-eyebrow',
            'teaser_title' => 'Test-teaser-titel',
            'teaser_checklist_items' => ['Een', 'Twee'],
            'lead_heading' => 'Test-lead-titel',
            'lead_expect_items' => ['Drie', 'Vier'],
            'accent_step_title' => 'Test-accentkleurenstap-titel',
        ]);

        $response = $this->getJson('/api/quiz-config');

        $response->assertOk();
        $response->assertJsonPath('sections.materials-colors.title', 'Test-titel');
        $response->assertJsonPath('sections.materials-colors.cta', 'Test-cta');
        $response->assertJsonPath('copy.resultHero.eyebrow', 'Test-eyebrow');
        $response->assertJsonPath('copy.reportTeaser.title', 'Test-teaser-titel');
        $response->assertJsonPath('copy.reportTeaser.checklistItems', ['Een', 'Twee']);
        $response->assertJsonPath('copy.leadForm.heading', 'Test-lead-titel');
        $response->assertJsonPath('copy.leadForm.expectItems', ['Drie', 'Vier']);
        $response->assertJsonPath('copy.accentColorStep.title', 'Test-accentkleurenstap-titel');
    }

    /**
     * Regressie: de admin kan opties binnen een vraag slepen (zie QuizOptionsPage::reorderOptions()),
     * maar deze respons negeerde sort_order en gaf altijd de aanmaakvolgorde terug — de klant-quiz
     * toonde daardoor nooit de door de admin ingestelde volgorde.
     */
    #[Test]
    public function de_opties_staan_in_de_door_de_admin_ingestelde_volgorde(): void
    {
        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken', 'sort_order' => 20, 'is_active' => true, 'has_image' => false,
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'grenen',
            'primary_style' => 'japandi', 'title' => 'Grenen', 'sort_order' => 10, 'is_active' => true, 'has_image' => false,
        ]);

        $response = $this->getJson('/api/quiz-config');

        $response->assertOk();
        $ids = collect($response->json('options'))->where('questionId', 'vloer')->pluck('id')->values()->all();
        $this->assertSame(['grenen', 'eiken'], $ids);
    }
}
