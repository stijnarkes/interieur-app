<?php

namespace Tests\Feature;

use App\Filament\Pages\StatsPage;
use App\Models\QuizEvent;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het periodefilter op de Statistieken-pagina (StatsPage::filtersForm()): leeg filter moet
 * zich gedragen als "alle tijd" (het oude gedrag, voor dit filter bestond), en een ingevuld
 * filter moet records van buiten de periode uitsluiten bij zowel de kerncijfers (QuizEvent) als
 * de top-stijlen/top-kenmerken (Submission).
 */
class StatsPageDateFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * created_at staat niet in $fillable (terecht — normaal beheert Eloquent dat zelf), dus
     * ->create(['created_at' => ...]) wordt stilletjes genegeerd door mass assignment protection.
     * forceFill()->save() omzeilt dat specifiek voor deze test, om records van buiten de
     * filterperiode te simuleren.
     */
    private function backdate(QuizEvent|Submission $model, \DateTimeInterface $date): void
    {
        $model->forceFill(['created_at' => $date])->save();
    }

    #[Test]
    public function zonder_filter_tellen_alle_quizevents_en_submissions_mee(): void
    {
        QuizEvent::record(QuizEvent::STARTED);
        $this->backdate(QuizEvent::record(QuizEvent::STARTED), now()->subYears(2));
        QuizEvent::record(QuizEvent::COMPLETED);
        QuizEvent::record(QuizEvent::LEAD_SUBMITTED);

        Submission::query()->create(['style' => 'Hotel luxe']);
        $this->backdate(Submission::query()->create(['style' => 'Hotel luxe']), now()->subYears(2));

        $page = new StatsPage;

        $stats = $page->getFunnelStats();
        $this->assertSame(2, $stats['started']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(1, $stats['leadSubmitted']);
        $this->assertSame(50.0, $stats['conversionRate']);

        $topStyles = $page->getTopStyles();
        $this->assertSame(2, $topStyles->firstWhere('style', 'Hotel luxe')['count']);
    }

    #[Test]
    public function met_filter_tellen_alleen_records_binnen_de_periode_mee(): void
    {
        $this->backdate(QuizEvent::record(QuizEvent::STARTED), now()->subYears(2));
        QuizEvent::record(QuizEvent::STARTED);
        QuizEvent::record(QuizEvent::LEAD_SUBMITTED);

        $this->backdate(Submission::query()->create(['style' => 'Hotel luxe']), now()->subYears(2));
        Submission::query()->create(['style' => 'Hotel luxe']);

        $page = new StatsPage;
        $page->filters = [
            'startDate' => now()->subDay()->toDateString(),
            'endDate' => now()->addDay()->toDateString(),
        ];

        $stats = $page->getFunnelStats();
        $this->assertSame(1, $stats['started']);
        $this->assertSame(1, $stats['leadSubmitted']);
        $this->assertSame(100.0, $stats['conversionRate']);

        $topStyles = $page->getTopStyles();
        $this->assertSame(1, $topStyles->firstWhere('style', 'Hotel luxe')['count']);
    }

    #[Test]
    public function zonder_enige_start_blijft_de_conversie_nul_i_p_v_te_delen_door_nul(): void
    {
        $page = new StatsPage;

        $stats = $page->getFunnelStats();

        $this->assertSame(0, $stats['started']);
        $this->assertSame(0.0, $stats['conversionRate']);
        $this->assertSame(0.0, $stats['completionRate']);
    }

    #[Test]
    public function de_pagina_rendert_het_periodefilter_en_de_kerncijfers_zonder_fouten(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(StatsPage::getUrl());

        $response->assertOk();
        $response->assertSee('Van');
        $response->assertSee('Tot');
        $response->assertSee('Keer gestart');
        $response->assertSee('Conversiepercentage');
    }
}
