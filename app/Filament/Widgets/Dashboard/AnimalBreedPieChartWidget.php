<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Breed;
use Filament\Widgets\ChartWidget;

class AnimalBreedPieChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Breed Distribution';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return auth()->user()?->can('view breeds') ?? false;
    }

    protected function getData(): array
    {
        $breeds = Breed::query()
            ->withCount([
                'animals as active_animals_count' => fn($query) => $query
                    ->where('status', 'Active')
                    ->where('is_archived', false),
            ])
            ->orderBy('breed_name')
            ->get()
            ->filter(fn($breed) => $breed->active_animals_count > 0)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Active Animals by Breed',
                    'data' => $breeds->pluck('active_animals_count')->toArray(),
                    'backgroundColor' => [
                        '#16a34a',
                        '#f59e0b',
                        '#3b82f6',
                        '#ef4444',
                        '#8b5cf6',
                        '#14b8a6',
                        '#f97316',
                        '#e11d48',
                        '#84cc16',
                        '#06b6d4',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => $breeds->pluck('breed_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
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
