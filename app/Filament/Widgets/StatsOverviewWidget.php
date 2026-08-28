<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->canViewResults() ?? false;
    }

    protected function getStats(): array
    {
        $mostCommonStyle = Submission::query()
            ->whereNotNull('quiz_result')
            ->select('style')
            ->groupBy('style')
            ->orderByRaw('COUNT(*) DESC')
            ->value('style');

        return [
            Stat::make('Totaal inzendingen', Submission::count())
                ->icon('heroicon-o-inbox-stack')
                ->color('primary'),

            Stat::make('Voltooide stijltests', Submission::whereNotNull('quiz_result')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Nieuwe leads (7 dagen)', Submission::whereNotNull('email')->where('created_at', '>=', now()->subDays(7))->count())
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make('Meest voorkomende woonstijl', $mostCommonStyle ?? '—')
                ->icon('heroicon-o-sparkles')
                ->color('warning'),

            Stat::make('Vandaag', Submission::whereDate('created_at', today())->count())
                ->icon('heroicon-o-calendar-days')
                ->color('gray'),

            Stat::make('E-mailadressen', Submission::whereNotNull('email')->count())
                ->icon('heroicon-o-envelope')
                ->color('gray'),
        ];
    }
}
