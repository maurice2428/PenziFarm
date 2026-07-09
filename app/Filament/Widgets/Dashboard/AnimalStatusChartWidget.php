<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Animal;
use Filament\Widgets\ChartWidget;

class AnimalStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Animal Lifecycle Status';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Animal Status',
                    'data' => [
                        Animal::query()->where('status', 'Active')->where('is_archived', false)->count(),
                        Animal::query()->where('status', 'Sold')->count(),
                        Animal::query()->where('status', 'Dead')->count(),
                        Animal::query()->where('status', 'Culled')->count(),
                    ],
                    'backgroundColor' => [
                        '#16a34a',
                        '#f59e0b',
                        '#ef4444',
                        '#64748b',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 12,
                ],
            ],
            'labels' => ['Active', 'Sold', 'Dead', 'Culled'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '68%',  // slightly cleaner center
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            // 🔥 THIS removes the background lines completely
            'scales' => [
                'x' => [
                    'display' => false,
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => false,
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
