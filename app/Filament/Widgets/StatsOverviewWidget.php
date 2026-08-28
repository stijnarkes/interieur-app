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

    /**
     * Eén samengestelde query i.p.v. 5 losse count()-aanroepen — dit widget draait op elk
     * bezoek aan het admin-dashboard mee, dus elke bespaarde round-trip naar de (soms trage)
     * database telt hier direct mee. Alleen "meest voorkomende woonstijl" heeft z'n eigen
     * GROUP BY nodig en blijft daarom een aparte query.
     */
    protected function getStats(): array
    {
        $totals = Submission::query()->selectRaw(
            'COUNT(*) as total,
             COUNT(CASE WHEN quiz_result IS NOT NULL THEN 1 END) as completed,
             COUNT(CASE WHEN email IS NOT NULL AND created_at >= ? THEN 1 END) as new_leads,
             COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as today,
             COUNT(CASE WHEN email IS NOT NULL THEN 1 END) as with_email',
            [now()->subDays(7), today()->toDateString()],
        )->first();

        $mostCommonStyle = Submission::query()
            ->whereNotNull('quiz_result')
            ->select('style')
            ->groupBy('style')
            ->orderByRaw('COUNT(*) DESC')
            ->value('style');

        return [
            Stat::make('Totaal inzendingen', $totals->total)
                ->icon('heroicon-o-inbox-stack')
                ->color('primary'),

            Stat::make('Voltooide stijltests', $totals->completed)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Nieuwe leads (7 dagen)', $totals->new_leads)
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make('Meest voorkomende woonstijl', $mostCommonStyle ?? '—')
                ->icon('heroicon-o-sparkles')
                ->color('warning'),

            Stat::make('Vandaag', $totals->today)
                ->icon('heroicon-o-calendar-days')
                ->color('gray'),

            Stat::make('E-mailadressen', $totals->with_email)
                ->icon('heroicon-o-envelope')
                ->color('gray'),
        ];
    }
}
