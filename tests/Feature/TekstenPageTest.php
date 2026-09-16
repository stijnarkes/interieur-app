<?php

namespace Tests\Feature;

use App\Filament\Pages\TekstenPage;
use App\Models\QuizTransitionSection;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TekstenPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function het_startscherm_kan_bewerkt_worden(): void
    {
        SiteContent::current();

        Livewire::actingAs($this->admin())
            ->test(TekstenPage::class)
            ->callAction('editStartScreen', data: [
                'start_title' => 'Nieuwe titel',
                'start_intro' => 'Nieuwe intro',
                'start_button_label' => 'Nieuwe knoptekst',
                'start_duration_fact' => '± 5 minuten',
                'start_questions_suffix' => 'stellingen',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Nieuwe titel', SiteContent::current()->start_title);
        $this->assertSame('± 5 minuten', SiteContent::current()->start_duration_fact);
    }

    #[Test]
    public function een_overgangsscherm_kan_bewerkt_worden(): void
    {
        $section = QuizTransitionSection::create([
            'section_id' => 'materials-colors',
            'title' => 'Oude titel',
            'tagline' => 'Oude tagline',
            'wrap_up' => null,
            'cta' => 'Oude cta',
        ]);

        Livewire::actingAs($this->admin())
            ->test(TekstenPage::class)
            ->callAction('editSection', data: [
                'title' => 'Nieuwe titel',
                'tagline' => 'Nieuwe tagline',
                'wrap_up' => 'Nieuwe afsluitzin',
                'cta' => 'Nieuwe cta',
            ], arguments: ['sectionId' => $section->section_id])
            ->assertHasNoActionErrors();

        $section->refresh();
        $this->assertSame('Nieuwe titel', $section->title);
        $this->assertSame('Nieuwe afsluitzin', $section->wrap_up);
    }

    #[Test]
    public function de_resultatenpagina_teksten_kunnen_bewerkt_worden_inclusief_lijstvelden(): void
    {
        SiteContent::current();

        Livewire::actingAs($this->admin())
            ->test(TekstenPage::class)
            ->callAction('editResultCopy', data: [
                'result_page_title' => 'Titel',
                'hero_eyebrow' => 'Eyebrow',
                'hero_expectation' => 'Verwachting',
                'hero_primary_label' => 'Basis',
                'hero_secondary_label' => 'Invloed',
                'teaser_title' => 'Teaser',
                'teaser_intro' => 'Teaser-intro',
                'teaser_list_intro' => 'Lijst-intro',
                'teaser_checklist_items' => ['Punt 1', 'Punt 2'],
                'teaser_mock_label' => 'Mock-label',
                'lead_heading' => 'Lead-titel',
                'lead_intro' => 'Lead-intro',
                'lead_optin_label' => 'Opt-in',
                'lead_submit_label' => 'Versturen',
                'lead_reassurance' => 'Geruststelling',
                'lead_success_title' => 'Gelukt',
                'lead_success_body' => 'Bedankt {name}, naar {email}.',
                'lead_queued_title' => 'Ontvangen',
                'lead_queued_body' => 'Komt eraan.',
                'lead_spam_hint' => 'Spam-hint',
                'lead_expect_title' => 'Verwacht-titel',
                'lead_expect_items' => ['A', 'B'],
                'lead_resend_label' => 'Opnieuw',
            ])
            ->assertHasNoActionErrors();

        $content = SiteContent::current();
        $this->assertSame('Titel', $content->result_page_title);
        $this->assertSame(['Punt 1', 'Punt 2'], $content->teaser_checklist_items);
        $this->assertSame(['A', 'B'], $content->lead_expect_items);
    }

    #[Test]
    public function de_accentkleurenstap_kan_bewerkt_worden(): void
    {
        SiteContent::current();

        Livewire::actingAs($this->admin())
            ->test(TekstenPage::class)
            ->callAction('editAccentColorStep', data: [
                'accent_step_title' => 'Nieuwe titel',
                'accent_step_intro' => 'Nieuwe intro',
                'accent_step_hint' => 'Nieuwe hint',
                'accent_step_continue_label' => 'Verder',
                'accent_step_chosen_title' => 'Nieuwe gekozen-titel',
                'accent_step_change_label' => 'Aanpassen',
                'accent_step_error_message' => 'Nieuwe foutmelding',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Nieuwe titel', SiteContent::current()->accent_step_title);
        $this->assertSame('Nieuwe foutmelding', SiteContent::current()->accent_step_error_message);
    }

    #[Test]
    public function de_e_mailtekst_kan_bewerkt_worden(): void
    {
        SiteContent::current();

        Livewire::actingAs($this->admin())
            ->test(TekstenPage::class)
            ->callAction('editEmailCopy', data: [
                'email_subject' => 'Nieuw onderwerp',
                'email_header' => 'Nieuwe header',
                'email_greeting' => 'Hallo',
                'email_intro' => 'Nieuwe intro',
                'email_outro' => 'Nieuwe outro',
                'email_cta_label' => 'Nieuwe cta',
                'email_cta_url' => 'https://example.test/interieuradvies',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Nieuw onderwerp', SiteContent::current()->email_subject);
    }
}
