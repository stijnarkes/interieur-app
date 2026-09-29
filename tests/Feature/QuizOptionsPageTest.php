<?php

namespace Tests\Feature;

use App\Filament\Pages\QuizOptionsPage;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dekt het slepen van antwoordopties binnen een vraag (zie QuizOptionsPage::reorderOptions()). */
class QuizOptionsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeOption(string $questionKey, string $slug, int $sortOrder): QuizOption
    {
        return QuizOption::create([
            'question_id' => $questionKey, 'style_key' => 'japandi', 'option_slug' => $slug,
            'primary_style' => 'japandi', 'title' => $slug, 'sort_order' => $sortOrder,
            'is_active' => true, 'has_image' => false,
        ]);
    }

    #[Test]
    public function slepen_slaat_de_nieuwe_volgorde_van_opties_op(): void
    {
        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        $first = $this->makeOption('vloer', 'eiken', 10);
        $second = $this->makeOption('vloer', 'grenen', 20);
        $third = $this->makeOption('vloer', 'notenhout', 30);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->call('reorderOptions', [(string) $third->id, (string) $first->id, (string) $second->id]);

        $this->assertSame(10, $third->fresh()->sort_order);
        $this->assertSame(20, $first->fresh()->sort_order);
        $this->assertSame(30, $second->fresh()->sort_order);
    }

    #[Test]
    public function nieuwe_opties_komen_achteraan_de_lijst_te_staan(): void
    {
        Storage::fake('public');
        Storage::fake('quiz_images');

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        $this->makeOption('vloer', 'eiken', 10);
        $this->makeOption('vloer', 'grenen', 20);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->mountAction('createOption', arguments: ['questionId' => 'vloer'])
            ->setActionData([
                'title' => 'Notenhout', 'style_keys' => ['japandi'],
                'image' => \Illuminate\Http\UploadedFile::fake()->image('notenhout.jpg'),
                'is_active' => true,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $newest = QuizOption::where('option_slug', 'like', 'vloer-notenhout-%')->firstOrFail();
        $this->assertSame(30, $newest->sort_order);
    }

    /**
     * Dekt de admin-bewerkbare stijlscores (zie QuizOptionsPage::styleScoreFields(),
     * QuizOption::style_scores) — nieuw sinds de opdracht "scoring woonstijltest Boer Staphorst":
     * een beheerder kan de 0-1-matchscores nu zelf invullen i.p.v. dat alleen een ontwikkelaar de
     * code (QuizAnswerScoreMatrix) kan wijzigen.
     */
    #[Test]
    public function een_nieuwe_optie_slaat_de_ingevulde_stijlscores_op_als_floats(): void
    {
        Storage::fake('public');
        Storage::fake('quiz_images');

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->mountAction('createOption', arguments: ['questionId' => 'vloer'])
            ->setActionData([
                'title' => 'Eiken', 'style_keys' => ['japandi'],
                'style_scores' => [
                    'hotelLuxe' => '0.30', 'landelijk' => '0.50', 'japandi' => '0.85',
                    'kleurExplosie' => '0.00', 'modern' => '0.70', 'scandinavisch' => '0.70',
                ],
                'image' => \Illuminate\Http\UploadedFile::fake()->image('eiken.jpg'),
                'is_active' => true,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $option = QuizOption::where('option_slug', 'like', 'vloer-eiken-%')->firstOrFail();
        // assertEquals (niet assertSame): de sleutelvolgorde in de opgeslagen JSON hoeft niet
        // gelijk te zijn aan hieronder, alleen de waarden per stijl.
        $this->assertEquals(
            ['hotelLuxe' => 0.30, 'landelijk' => 0.50, 'japandi' => 0.85, 'kleurExplosie' => 0.00, 'modern' => 0.70, 'scandinavisch' => 0.70],
            $option->style_scores,
        );
        $this->assertIsFloat($option->style_scores['japandi'], 'Filament geeft Select-waarden als strings terug — die moeten expliciet naar float.');
    }

    #[Test]
    public function het_bewerken_van_stijlscores_werkt_meteen_door_in_de_uitslagberekening(): void
    {
        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);
        $option = $this->makeOption('vloer', 'eiken', 10);
        $option->update(['style_scores' => ['hotelLuxe' => 0.0, 'landelijk' => 0.0, 'japandi' => 0.30, 'kleurExplosie' => 0.0, 'modern' => 0.0, 'scandinavisch' => 0.0]]);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->mountAction('editOption', arguments: ['optionId' => $option->id])
            ->assertActionDataSet(['style_scores' => ['hotelLuxe' => '0.00', 'landelijk' => '0.00', 'japandi' => '0.30', 'kleurExplosie' => '0.00', 'modern' => '0.00', 'scandinavisch' => '0.00']])
            ->setActionData([
                'title' => $option->title, 'style_keys' => ['japandi'],
                'style_scores' => [
                    'hotelLuxe' => '0.00', 'landelijk' => '0.00', 'japandi' => '1.00',
                    'kleurExplosie' => '0.00', 'modern' => '0.00', 'scandinavisch' => '0.00',
                ],
                'is_active' => true,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        // scoreFor() i.p.v. rechtstreeks de array uitlezen: JSON bewaart een hele-getal-score als
        // "1" (geen ".0"), dus json_decode geeft hier een PHP-int terug — scoreFor() cast altijd
        // expliciet naar float, exact zoals QuizScoringService 'm ook gebruikt.
        $this->assertSame(1.0, $option->fresh()->scoreFor('japandi'));

        $computed = app(QuizScoringService::class)->compute(['vloer' => ['eiken']]);
        $this->assertSame(1.0, $computed['style_scores']['japandi'], 'De aangepaste score in de admin moet meteen meetellen in een nieuwe berekening.');
    }
}
