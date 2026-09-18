<?php

namespace App\Filament\Widgets;

use App\Models\QuizEvent;
use App\Support\QuizStructure;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Toont waar bezoekers in de hoofdquiz afhaken: gestart -> per vraag (in volgorde) bereikt ->
 * afgerond -> aanvraag verstuurd. Zie App\Models\QuizEvent voor de PII-vrije telling erachter.
 * Bewust een losse widget naast StyleChartWidget (die draait op Submission — dus alleen mensen die
 * al helemaal klaar zijn): dit laat juist zien wat daarvóór gebeurt.
 */
class QuizFunnelChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Trechter: van start tot aanvraag';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
    }

    protected function getData(): array
    {
        $counts = QuizEvent::query()
            ->selectRaw('name, question_key, COUNT(*) as total')
            ->groupBy('name', 'question_key')
            ->get();

        $countFor = fn (string $name): int => (int) $counts->where('name', $name)->sum('total');

        $questionCounts = $counts
            ->where('name', QuizEvent::QUESTION_REACHED)
            ->pluck('total', 'question_key');

        $labels = ['Gestart'];
        $data = [$countFor(QuizEvent::STARTED)];

        foreach (QuizStructure::questions() as $questionKey => $question) {
            $labels[] = $question['title'];
            $data[] = (int) ($questionCounts[$questionKey] ?? 0);
        }

        $labels[] = 'Afgerond';
        $data[] = $countFor(QuizEvent::COMPLETED);
        $labels[] = 'Aanvraag verstuurd';
        $data[] = $countFor(QuizEvent::LEAD_SUBMITTED);

        return [
            'datasets' => [
                [
                    'label' => 'Bezoekers',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
