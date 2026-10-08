<?php

namespace App\Filament\Pages;

use App\Models\QuizEvent;
use App\Models\Submission;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StatsPage extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Statistieken';

    protected static ?string $navigationGroup = 'Resultaten';

    protected static ?string $title = 'Statistieken';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.stats-page';

    /** De blade-view roept getTopTraits() twee keer aan (lijst + leeg-check) — dit voorkomt dat
     *  de volledige quiz_result-kolom van alle inzendingen daardoor twee keer wordt opgehaald
     *  en in PHP verwerkt. */
    protected ?Collection $topTraits = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
    }

    /**
     * Periodefilter bovenaan de pagina — leeg (geen van beide data ingevuld) betekent "alle tijd",
     * precies het gedrag van vóór dit filter, dus niemand merkt iets zolang het filter niet gebruikt
     * wordt. Doorgegeven aan QuizFunnelChartWidget via het 'filters'-prop (zie stats-page.blade.php
     * en Filament\Widgets\Concerns\InteractsWithPageFilters daar), en hier zelf gebruikt voor de
     * kerncijfers/top-stijlen/top-kenmerken.
     */
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')->label('Van'),
                DatePicker::make('endDate')->label('Tot'),
            ])
            ->columns(2);
    }

    protected function startDate(): ?Carbon
    {
        $value = $this->filters['startDate'] ?? null;

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    protected function endDate(): ?Carbon
    {
        $value = $this->filters['endDate'] ?? null;

        return $value ? Carbon::parse($value)->endOfDay() : null;
    }

    protected function applyDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        return $query
            ->when($this->startDate(), fn (Builder $query, Carbon $date) => $query->where($column, '>=', $date))
            ->when($this->endDate(), fn (Builder $query, Carbon $date) => $query->where($column, '<=', $date));
    }

    /**
     * Kerncijfers bovenaan de pagina, gebaseerd op dezelfde PII-vrije QuizEvent-tellingen als
     * QuizFunnelChartWidget — conversie/afronding zijn verhoudingsgetallen t.o.v. "Gestart", dus 0
     * als er nog niemand gestart is i.p.v. te delen door nul.
     *
     * @return array{started: int, completed: int, leadSubmitted: int, completionRate: float, conversionRate: float}
     */
    public function getFunnelStats(): array
    {
        $countFor = function (string $name): int {
            return (int) $this->applyDateRange(QuizEvent::query())->where('name', $name)->count();
        };

        $started = $countFor(QuizEvent::STARTED);
        $completed = $countFor(QuizEvent::COMPLETED);
        $leadSubmitted = $countFor(QuizEvent::LEAD_SUBMITTED);

        return [
            'started' => $started,
            'completed' => $completed,
            'leadSubmitted' => $leadSubmitted,
            'completionRate' => $started > 0 ? round($completed / $started * 100, 1) : 0.0,
            'conversionRate' => $started > 0 ? round($leadSubmitted / $started * 100, 1) : 0.0,
        ];
    }

    public function getTopStyles(): Collection
    {
        $total = $this->applyDateRange(Submission::query())->count();

        return $this->applyDateRange(Submission::query())
            ->selectRaw('style, COUNT(*) as count')
            ->groupBy('style')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'style' => $row->style,
                'count' => $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ]);
    }

    /** Kenmerken (traits) van de winnende stijl, opgeteld over alle Woondroomtest-inzendingen. */
    public function getTopTraits(): Collection
    {
        return $this->topTraits ??= $this->computeTopTraits();
    }

    protected function computeTopTraits(): Collection
    {
        $counts = [];
        $query = $this->applyDateRange(Submission::query())->whereNotNull('quiz_result');

        foreach ($query->pluck('quiz_result') as $result) {
            foreach ($result['traits'] ?? [] as $trait) {
                $counts[$trait] = ($counts[$trait] ?? 0) + 1;
            }
        }
        arsort($counts);

        return collect(array_slice($counts, 0, 20, true))
            ->map(fn ($count, $word) => ['word' => $word, 'count' => $count]);
    }

}
