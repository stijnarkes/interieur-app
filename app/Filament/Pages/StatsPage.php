<?php

namespace App\Filament\Pages;

use App\Models\Submission;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class StatsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Statistieken';

    protected static ?string $title = 'Statistieken';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.stats-page';

    public function getTopStyles(): Collection
    {
        $total = Submission::count();

        return Submission::query()
            ->selectRaw('style, COUNT(*) as count')
            ->groupBy('style')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'style'      => $row->style,
                'count'      => $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ]);
    }

    public function getTopMoodWords(): Collection
    {
        return $this->countCommaList(
            Submission::whereNotNull('mood_words')->pluck('mood_words')
        );
    }

    public function getTopColors(): Collection
    {
        return $this->countCommaList(
            Submission::whereNotNull('colors')->pluck('colors')
        );
    }

    private function countCommaList(Collection $rows): Collection
    {
        $counts = [];
        foreach ($rows as $row) {
            foreach (explode(',', $row) as $item) {
                $item = trim(strtolower($item));
                if ($item !== '') {
                    $counts[$item] = ($counts[$item] ?? 0) + 1;
                }
            }
        }
        arsort($counts);

        return collect(array_slice($counts, 0, 20, true))
            ->map(fn ($count, $word) => ['word' => $word, 'count' => $count]);
    }
}
