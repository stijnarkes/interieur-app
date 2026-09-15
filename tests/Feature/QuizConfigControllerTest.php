<?php

namespace Tests\Feature;

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
    }
}
