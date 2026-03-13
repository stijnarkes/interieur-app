<?php

namespace App\Filament\Widgets;

use App\Models\Submission;
use Filament\Widgets\ChartWidget;

class StyleChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Inzendingen per stijl';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Submission::query()
            ->selectRaw('style, COUNT(*) as count')
            ->groupBy('style')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'style');

        return [
            'datasets' => [
                [
                    'label'           => 'Inzendingen',
                    'data'            => $data->values()->toArray(),
                    'backgroundColor' => [
                        '#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6',
                        '#f97316', '#06b6d4', '#ec4899', '#84cc16', '#6366f1',
                    ],
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
