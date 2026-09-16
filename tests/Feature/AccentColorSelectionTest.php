<?php

namespace Tests\Feature;

use App\Models\AccentColor;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de accentkleurstap ná de stijlberekening: welke kleuren worden aangeboden (op basis van
 * het al berekende primary_style/secondary_style, zonder de scoreberekening zelf aan te raken —
 * zie App\Services\AccentColorSelector), en de opslag/validatie van de keuze van de bezoeker.
 */
class AccentColorSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // De migratie die de definitieve accentkleurencatalogus zet
        // (2026_09_16_141703_replace_accent_color_catalog) draait ook hier mee — deze tests
        // willen een schone catalogus met alleen hun eigen fixtures, dus die 33 kleuren eerst weg.
        AccentColor::query()->delete();
    }

    private function makeQuestionAndOption(): QuizOption
    {
        StyleProfile::create([
            'style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi',
            'long_description' => 'Rust en warmte passen bij jou.',
        ]);

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        return QuizOption::create([
            'question_id' => 'vloer', 'style_key' => 'japandi', 'option_slug' => 'eiken',
            'primary_style' => 'japandi', 'title' => 'Eiken vloer', 'is_active' => true, 'has_image' => false,
        ]);
    }

    private function makeQuizResult(?string $primary = 'japandi', ?string $secondary = null): QuizResult
    {
        return QuizResult::create([
            'uuid' => (string) Str::uuid(),
            'answers' => ['vloer' => ['eiken']],
            'style_scores' => ['japandi' => 1],
            'primary_style' => $primary,
            'secondary_style' => $secondary,
        ]);
    }

    #[Test]
    public function het_resultaat_bevat_alleen_accentkleuren_die_aan_de_primaire_stijl_gekoppeld_zijn(): void
    {
        $this->makeQuestionAndOption();
        AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        AccentColor::create(['name' => 'Kobaltblauw', 'hex' => '#1d4e89', 'style_keys' => ['modern']]);

        $response = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['eiken']]]);

        $response->assertOk();
        $names = collect($response->json('accentColorOptions'))->pluck('name')->all();
        $this->assertSame(['Mosgroen'], $names);
    }

    #[Test]
    public function een_significante_secundaire_stijl_vult_de_kleurenset_aan(): void
    {
        $this->makeQuestionAndOption();
        AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        AccentColor::create(['name' => 'Antraciet', 'hex' => '#33363a', 'style_keys' => ['modern']]);
        $quizResult = $this->makeQuizResult('japandi', 'modern');

        $response = $this->getAccentColorOptionsFor($quizResult);

        $this->assertEqualsCanonicalizing(['Mosgroen', 'Antraciet'], collect($response)->pluck('name')->all());
    }

    #[Test]
    public function een_geldige_keuze_van_een_kleur_wordt_opgeslagen(): void
    {
        $this->makeQuestionAndOption();
        $color = AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        $quizResult = $this->makeQuizResult();

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/accent-colors", [
            'accentColorIds' => [$color->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Mosgroen', 'hex' => '#6b7a4f']);
        $this->assertSame(
            [['id' => $color->id, 'name' => 'Mosgroen', 'hex' => '#6b7a4f']],
            $quizResult->fresh()->chosen_accent_colors,
        );
    }

    #[Test]
    public function een_derde_kleur_kan_niet_geselecteerd_worden(): void
    {
        $this->makeQuestionAndOption();
        $a = AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        $b = AccentColor::create(['name' => 'Terracotta', 'hex' => '#c1694f', 'style_keys' => ['japandi']]);
        $c = AccentColor::create(['name' => 'Taupe', 'hex' => '#a8967d', 'style_keys' => ['japandi']]);
        $quizResult = $this->makeQuizResult();

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/accent-colors", [
            'accentColorIds' => [$a->id, $b->id, $c->id],
        ]);

        $response->assertStatus(422);
        $this->assertNull($quizResult->fresh()->chosen_accent_colors);
    }

    #[Test]
    public function een_kleur_die_niet_bij_het_berekende_stijlprofiel_past_wordt_geweigerd(): void
    {
        $this->makeQuestionAndOption();
        $foreign = AccentColor::create(['name' => 'Kobaltblauw', 'hex' => '#1d4e89', 'style_keys' => ['modern']]);
        $quizResult = $this->makeQuizResult('japandi', null);

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/accent-colors", [
            'accentColorIds' => [$foreign->id],
        ]);

        $response->assertStatus(422);
        $this->assertNull($quizResult->fresh()->chosen_accent_colors);
    }

    #[Test]
    public function een_lege_keuze_is_geldig_en_wordt_opgeslagen_als_bewust_geen_accentkleur(): void
    {
        $this->makeQuestionAndOption();
        AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        $quizResult = $this->makeQuizResult();

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/accent-colors", [
            'accentColorIds' => [],
        ]);

        $response->assertOk();
        $response->assertExactJson(['accentColors' => []]);
        $this->assertSame([], $quizResult->fresh()->chosen_accent_colors);
    }

    #[Test]
    public function een_kleur_die_exact_de_hex_van_het_gekozen_basispalet_deelt_wordt_geweigerd(): void
    {
        $this->makeQuestionAndOption();
        $color = AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        $quizResult = $this->makeQuizResult();
        $quizResult->update([
            'chosen_base_palette' => [
                'id' => 1,
                'name' => 'Test-palet',
                'description' => 'Test',
                // Zelfde hex als de accentkleur hierboven, met bewust afwijkende hoofdlettering —
                // de vergelijking moet case-insensitief zijn.
                'colors' => [['name' => 'Mosgroen', 'hex' => '#6B7A4F']],
            ],
        ]);

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/accent-colors", [
            'accentColorIds' => [$color->id],
        ]);

        $response->assertStatus(422);
        $this->assertNull($quizResult->fresh()->chosen_accent_colors);
    }

    #[Test]
    public function een_resultaat_zonder_primaire_stijl_biedt_geen_accentkleuren_aan(): void
    {
        $this->makeQuestionAndOption();
        AccentColor::create(['name' => 'Mosgroen', 'hex' => '#6b7a4f', 'style_keys' => ['japandi']]);
        $quizResult = $this->makeQuizResult(null, null);

        $response = $this->getAccentColorOptionsFor($quizResult);

        $this->assertSame([], $response);
    }

    /** @return array<int, array{id: int, name: string, hex: string}> */
    private function getAccentColorOptionsFor(QuizResult $quizResult): array
    {
        // /api/quiz-result berekent altijd een nieuw resultaat — voor deze tests (die een al
        // bepaalde primary/secondary_style willen testen, inclusief een niet-significante
        // combinatie die de scoring zelf nooit zo zou opleveren) wordt de selector rechtstreeks
        // bevraagd via dezelfde route-actie i.p.v. via de volledige scoring-flow.
        return app(\App\Services\AccentColorSelector::class)
            ->forResult($quizResult->primary_style, $quizResult->secondary_style)
            ->map(fn ($color) => $color->toOptionArray())
            ->all();
    }
}
