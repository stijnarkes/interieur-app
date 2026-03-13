<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentSubmissionsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\StyleChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RecentSubmissionsWidget::class,
            StyleChartWidget::class,
        ];
    }
}
