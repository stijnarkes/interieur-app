<?php

namespace Tests\Feature;

use App\Models\BasePalette;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de admin-voorbeeldweergave (zie QuizPreviewController): alleen-lezen pagina's die precies
 * tonen hoe een vraag, overgangsscherm, de resultatenpagina of de bevestigingsmail eruitziet,
 * zonder de klant-quiz te moeten doorlopen. De belangrijkste garantie is dat dit nergens iets
 * opslaat/verzendt — geen nieuwe QuizResult/Submission-rij, ongeacht welke stijl bekeken wordt.
 */
class QuizPreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeQuestionWithOptions(): QuizQuestion
    {
        $question = QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
        ]);

        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'beton',
            'primary_style' => 'japandi', 'title' => 'Betonvloer', 'is_active' => false, 'has_image' => false,
        ]);

        return $question;
    }

    #[Test]
    public function elke_voorbeeldroute_vereist_een_ingelogde_gebruiker(): void
    {
        $this->makeQuestionWithOptions();

        $this->get('/admin/voorbeeld/vraag/vloer')->assertRedirect('/admin/login');
        $this->get('/admin/voorbeeld/overgangsscherm/materials-colors')->assertRedirect('/admin/login');
        $this->get('/admin/voorbeeld/resultaat')->assertRedirect('/admin/login');
        $this->get('/admin/voorbeeld/email')->assertRedirect('/admin/login');
    }

    #[Test]
    public function het_vraagvoorbeeld_toont_de_titel_en_alleen_actieve_opties(): void
    {
        $this->makeQuestionWithOptions();

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/vraag/vloer');

        $response->assertOk();
        $response->assertSee('Welke vloer?');
        $response->assertSee('Eiken vloer');
        $response->assertDontSee('Betonvloer');
    }

    /**
     * Regressie: deze query had geen orderBy('sort_order'), dus het voorbeeld toonde altijd de
     * aanmaakvolgorde in plaats van de door de admin ingestelde (gesleepte) volgorde.
     */
    #[Test]
    public function het_vraagvoorbeeld_toont_de_opties_in_de_door_de_admin_ingestelde_volgorde(): void
    {
        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'sort_order' => 20, 'is_active' => true, 'has_image' => false,
        ]);
        QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'grenen',
            'primary_style' => 'japandi', 'title' => 'Grenen vloer', 'sort_order' => 10, 'is_active' => true, 'has_image' => false,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/vraag/vloer');

        $response->assertOk();
        $positionOfGrenen = strpos($response->getContent(), 'Grenen vloer');
        $positionOfEiken = strpos($response->getContent(), 'Eiken vloer');
        $this->assertLessThan($positionOfEiken, $positionOfGrenen, 'Grenen (sort_order 10) moet vóór Eiken (sort_order 20) getoond worden.');
    }

    #[Test]
    public function het_overgangsscherm_voorbeeld_toont_de_admin_teksten(): void
    {
        \App\Models\QuizTransitionSection::create([
            'section_id' => 'materials-colors',
            'title' => 'Test-titel voor overgang',
            'tagline' => 'Test-tagline',
            'wrap_up' => null,
            'cta' => 'Test-cta',
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/overgangsscherm/materials-colors');

        $response->assertOk();
        $response->assertSee('Test-titel voor overgang');
        $response->assertSee('Test-cta');
    }

    #[Test]
    public function een_onbestaand_overgangsscherm_geeft_een_nette_404(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/overgangsscherm/onbestaand');

        $response->assertNotFound();
    }

    #[Test]
    public function het_resultaatvoorbeeld_toont_de_losse_stijltekst_zonder_secundaire_stijl(): void
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi', 'long_description' => 'Rustig en warm.']);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/resultaat?style=japandi');

        $response->assertOk();
        $response->assertSee('Jouw woonstijl: Japandi', false);
        $response->assertDontSee('-invloeden');
    }

    #[Test]
    public function het_resultaatvoorbeeld_toont_de_invloedstekst_met_een_secundaire_stijl(): void
    {
        StyleProfile::create(['style_key' => 'hotelLuxe', 'label' => 'Hotel luxe', 'slug' => 'hotel-luxe', 'long_description' => 'Weelderig.']);
        StyleProfile::create(['style_key' => 'landelijk', 'label' => 'Landelijk', 'slug' => 'landelijk', 'long_description' => 'Warm en natuurlijk.']);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/resultaat?style=hotelLuxe&secondary=landelijk');

        $response->assertOk();
        $response->assertSee('Hotel luxe met Landelijk-invloeden', false);
    }

    #[Test]
    public function het_resultaatvoorbeeld_crasht_niet_zonder_geconfigureerde_accentkleuren_of_basispaletten(): void
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi', 'long_description' => 'Rustig en warm.']);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/resultaat?style=japandi');

        $response->assertOk();
    }

    #[Test]
    public function het_resultaatvoorbeeld_toont_de_basispaletten_van_de_gekozen_stijl(): void
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi', 'long_description' => 'Rustig en warm.']);
        BasePalette::create(['style_key' => 'japandi', 'name' => 'Test-basispalet', 'description' => 'Test', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]]);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/resultaat?style=japandi');

        $response->assertOk();
        $response->assertSee('Test-basispalet', false);
    }

    #[Test]
    public function het_e_mailvoorbeeld_bevat_de_huidige_sitecontent_teksten(): void
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi', 'long_description' => 'Rustig en warm.']);
        \App\Models\SiteContent::current()->update(['email_header' => 'Test-e-mailheader']);

        $response = $this->actingAs($this->admin())->get('/admin/voorbeeld/email?style=japandi');

        $response->assertOk();
        $response->assertSee('Test-e-mailheader');
    }

    #[Test]
    public function geen_van_de_voorbeeldpaginas_maakt_een_quizresult_of_submission_rij_aan(): void
    {
        $this->makeQuestionWithOptions();
        StyleProfile::create(['style_key' => 'hotelLuxe', 'label' => 'Hotel luxe', 'slug' => 'hotel-luxe', 'long_description' => 'Weelderig.']);
        StyleProfile::create(['style_key' => 'landelijk', 'label' => 'Landelijk', 'slug' => 'landelijk', 'long_description' => 'Warm.']);

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/voorbeeld/vraag/vloer');
        $this->actingAs($admin)->get('/admin/voorbeeld/overgangsscherm/materials-colors');
        $this->actingAs($admin)->get('/admin/voorbeeld/resultaat?style=hotelLuxe&secondary=landelijk');
        $this->actingAs($admin)->get('/admin/voorbeeld/email?style=hotelLuxe');

        $this->assertSame(0, QuizResult::count());
        $this->assertSame(0, Submission::count());
    }
}
