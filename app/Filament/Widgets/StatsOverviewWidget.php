<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Totaal inzendingen', Submission::count())
                ->icon('heroicon-o-inbox-stack')
                ->color('primary'),

            Stat::make('Vandaag', Submission::whereDate('created_at', today())->count())
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Afgelopen 7 dagen', Submission::where('created_at', '>=', now()->subDays(7))->count())
                ->icon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make('E-mailadressen', Submission::whereNotNull('email')->count())
                ->icon('heroicon-o-envelope')
                ->color('warning'),

            Stat::make('Met kamerafoto', Submission::where('has_room_photo', true)->count())
                ->icon('heroicon-o-photo')
                ->color('gray'),

            Stat::make('Resultaten gegenereerd', Submission::where('result_generated', true)->count())
                ->icon('heroicon-o-sparkles')
                ->color('success'),
        ];
    }
}
