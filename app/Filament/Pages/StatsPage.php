<?php

namespace App\Filament\Pages;

use App\Models\Submission;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StatsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Statistieken';

    protected static ?string $navigationGroup = 'Resultaten';

    protected static ?string $title = 'Statistieken';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.stats-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
    }

    public function getTopStyles(): Collection
    {
        $total = Submission::count();

        return Submission::query()
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

    /** Kenmerken (traits) van de winnende stijl, opgeteld over alle stijltest-inzendingen. */
    public function getTopTraits(): Collection
    {
        $counts = [];
        foreach (Submission::whereNotNull('quiz_result')->pluck('quiz_result') as $result) {
            foreach ($result['traits'] ?? [] as $trait) {
                $counts[$trait] = ($counts[$trait] ?? 0) + 1;
            }
        }
        arsort($counts);

        return collect(array_slice($counts, 0, 20, true))
            ->map(fn ($count, $word) => ['word' => $word, 'count' => $count]);
    }

}
