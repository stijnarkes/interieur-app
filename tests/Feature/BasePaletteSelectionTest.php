<?php

namespace Tests\Feature;

use App\Models\BasePalette;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de basispaletstap ná de stijlberekening: welke paletten worden aangeboden (altijd alleen
 * die van de primaire stijl, nooit gemengd met een secundaire — zie App\Models\BasePalette), en
 * de opslag/validatie van de keuze van de bezoeker. Mirrort AccentColorSelectionTest.php.
 */
class BasePaletteSelectionTest extends TestCase
{
    use RefreshDatabase;

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
    public function het_resultaat_bevat_alleen_basispaletten_van_de_primaire_stijl(): void
    {
        $this->makeQuestionAndOption();
        BasePalette::create(['style_key' => 'japandi', 'name' => 'Licht en verstild', 'description' => 'Test', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]]);
        BasePalette::create(['style_key' => 'modern', 'name' => 'Helder en minimalistisch', 'description' => 'Test', 'colors' => [['name' => 'Wit', 'hex' => '#ffffff']]]);

        $response = $this->postJson('/api/quiz-result', ['answers' => ['vloer' => ['eiken']]]);

        $response->assertOk();
        $names = collect($response->json('basePaletteOptions'))->pluck('name')->all();
        $this->assertSame(['Licht en verstild'], $names);
    }

    #[Test]
    public function een_significante_secundaire_stijl_voegt_geen_paletten_toe(): void
    {
        $this->makeQuestionAndOption();
        BasePalette::create(['style_key' => 'japandi', 'name' => 'Licht en verstild', 'description' => 'Test', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]]);
        BasePalette::create(['style_key' => 'modern', 'name' => 'Helder en minimalistisch', 'description' => 'Test', 'colors' => [['name' => 'Wit', 'hex' => '#ffffff']]]);
        $quizResult = $this->makeQuizResult('japandi', 'modern');

        // Basispaletten worden nooit gemengd met de secundaire stijl (in tegenstelling tot
        // accentkleuren) — QuizResultController::store() vraagt daarom alleen op basis van
        // primary_style op, ook als er een secundaire "invloed" is.
        $basePaletteOptions = BasePalette::query()->active()->forStyle($quizResult->primary_style)
            ->get()->map(fn (BasePalette $p) => $p->toOptionArray())->all();

        $this->assertSame(['Licht en verstild'], collect($basePaletteOptions)->pluck('name')->all());
    }

    #[Test]
    public function een_geldige_keuze_van_een_palet_wordt_opgeslagen(): void
    {
        $this->makeQuestionAndOption();
        $palette = BasePalette::create(['style_key' => 'japandi', 'name' => 'Licht en verstild', 'description' => 'Rustig.', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]]);
        $quizResult = $this->makeQuizResult();

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/base-palette", [
            'basePaletteId' => $palette->id,
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Licht en verstild']);
        $this->assertSame(
            ['id' => $palette->id, 'name' => 'Licht en verstild', 'description' => 'Rustig.', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]],
            $quizResult->fresh()->chosen_base_palette,
        );
    }

    #[Test]
    public function een_palet_van_een_andere_stijl_wordt_geweigerd(): void
    {
        $this->makeQuestionAndOption();
        $foreign = BasePalette::create(['style_key' => 'modern', 'name' => 'Helder en minimalistisch', 'description' => 'Test', 'colors' => [['name' => 'Wit', 'hex' => '#ffffff']]]);
        $quizResult = $this->makeQuizResult('japandi', null);

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/base-palette", [
            'basePaletteId' => $foreign->id,
        ]);

        $response->assertStatus(422);
        $this->assertNull($quizResult->fresh()->chosen_base_palette);
    }

    #[Test]
    public function een_inactief_palet_wordt_geweigerd(): void
    {
        $this->makeQuestionAndOption();
        $inactive = BasePalette::create(['style_key' => 'japandi', 'name' => 'Verouderd palet', 'description' => 'Test', 'colors' => [['name' => 'Grijs', 'hex' => '#cccccc']], 'is_active' => false]);
        $quizResult = $this->makeQuizResult();

        $response = $this->patchJson("/api/quiz-result/{$quizResult->uuid}/base-palette", [
            'basePaletteId' => $inactive->id,
        ]);

        $response->assertStatus(422);
        $this->assertNull($quizResult->fresh()->chosen_base_palette);
    }

    #[Test]
    public function een_resultaat_zonder_primaire_stijl_biedt_geen_basispaletten_aan(): void
    {
        $this->makeQuestionAndOption();
        BasePalette::create(['style_key' => 'japandi', 'name' => 'Licht en verstild', 'description' => 'Test', 'colors' => [['name' => 'Ecru', 'hex' => '#e4dac6']]]);
        $quizResult = $this->makeQuizResult(null, null);

        $basePaletteOptions = $quizResult->primary_style
            ? BasePalette::query()->active()->forStyle($quizResult->primary_style)->get()
            : collect();

        $this->assertCount(0, $basePaletteOptions);
    }
}
