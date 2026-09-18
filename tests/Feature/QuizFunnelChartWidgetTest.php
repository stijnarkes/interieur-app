<?php

namespace Tests\Feature;

use App\Filament\Pages\StatsPage;
use App\Filament\Widgets\QuizFunnelChartWidget;
use App\Models\QuizEvent;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dekt de trechtergrafiek op de Statistieken-pagina (zie App\Models\QuizEvent). */
class QuizFunnelChartWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function de_statistiekenpagina_laadt_met_de_trechtergrafiek(): void
    {
        $this->actingAs($this->admin())
            ->get(StatsPage::getUrl())
            ->assertOk()
            ->assertSeeLivewire(QuizFunnelChartWidget::class);
    }

    #[Test]
    public function de_grafiek_toont_de_tellingen_per_stap_in_de_juiste_volgorde(): void
    {
        // De create_quiz_questions_table-migratie zaait zelf al 11 echte vragen — die moeten weg
        // voordat we hier een schone, voorspelbare vragenlijst met precies 1 vraag opbouwen (zelfde
        // patroon als AccentColorSelectionTest::setUp() voor de vergelijkbare accent-kleurenzaai).
        QuizQuestion::query()->delete();

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        QuizEvent::record(QuizEvent::STARTED);
        QuizEvent::record(QuizEvent::STARTED);
        QuizEvent::record(QuizEvent::QUESTION_REACHED, 'vloer');
        QuizEvent::record(QuizEvent::COMPLETED);
        QuizEvent::record(QuizEvent::LEAD_SUBMITTED);

        // getData() is protected — via reflectie de daadwerkelijke chartdata ophalen.
        $widget = new QuizFunnelChartWidget;
        $method = new \ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);
        $chart = $method->invoke($widget);

        $this->assertSame(['Gestart', 'Welke vloer?', 'Afgerond', 'Aanvraag verstuurd'], $chart['labels']);
        $this->assertSame([2, 1, 1, 1], $chart['datasets'][0]['data']);
    }
}
