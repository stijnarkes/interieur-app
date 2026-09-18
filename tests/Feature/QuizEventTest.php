<?php

namespace Tests\Feature;

use App\Models\QuizEvent;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de PII-vrije trechtertelling voor de hoofdquiz (zie App\Models\QuizEvent) — "gestart" en
 * "vraag bereikt" komen van de client (QuizEventController), "afgerond" en "aanvraag verstuurd"
 * registreert de server zelf, rechtstreeks vanuit QuizResultController/QuizLeadController.
 */
class QuizEventTest extends TestCase
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

    #[Test]
    public function quiz_started_en_question_reached_worden_geaccepteerd(): void
    {
        $this->makeQuestionAndOption();

        $this->postJson('/api/quiz-events', ['name' => 'quiz_started'])->assertOk();
        $this->postJson('/api/quiz-events', ['name' => 'question_reached', 'questionKey' => 'vloer'])->assertOk();

        $this->assertSame(1, QuizEvent::where('name', 'quiz_started')->count());
        $this->assertSame(1, QuizEvent::where('name', 'question_reached')->where('question_key', 'vloer')->count());
    }

    #[Test]
    public function een_onbekende_event_naam_wordt_geweigerd(): void
    {
        $this->postJson('/api/quiz-events', ['name' => 'quiz_completed'])->assertStatus(422);
        $this->postJson('/api/quiz-events', ['name' => 'iets_verzonnens'])->assertStatus(422);

        $this->assertSame(0, QuizEvent::count());
    }

    #[Test]
    public function een_niet_bestaande_vraagsleutel_wordt_geweigerd(): void
    {
        $this->postJson('/api/quiz-events', ['name' => 'question_reached', 'questionKey' => 'onbestaand'])
            ->assertStatus(422);

        $this->assertSame(0, QuizEvent::count());
    }

    #[Test]
    public function het_afronden_van_de_test_registreert_zelf_een_quiz_completed_event(): void
    {
        $option = $this->makeQuestionAndOption();

        $this->postJson('/api/quiz-result', ['answers' => ['vloer' => [$option->option_slug]]])->assertOk();

        $this->assertSame(1, QuizEvent::where('name', QuizEvent::COMPLETED)->count());
    }

    #[Test]
    public function het_versturen_van_het_aanvraagformulier_registreert_precies_een_lead_submitted_event_ook_bij_een_retry(): void
    {
        $option = $this->makeQuestionAndOption();

        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => ['vloer' => [$option->option_slug]],
            'style_scores' => ['japandi' => 1], 'primary_style' => 'japandi',
        ]);

        $this->postJson('/api/quiz-lead', [
            'resultUuid' => $result->uuid, 'name' => 'Test', 'email' => 'test@example.com',
        ])->assertOk();

        // Simuleert een "opnieuw versturen" na een eerdere mislukking.
        Submission::where('quiz_result_id', $result->id)->update(['email_status' => 'failed']);
        $this->postJson('/api/quiz-lead', [
            'resultUuid' => $result->uuid, 'name' => 'Test', 'email' => 'test@example.com',
        ])->assertOk();

        $this->assertSame(1, QuizEvent::where('name', QuizEvent::LEAD_SUBMITTED)->count());
    }
}
