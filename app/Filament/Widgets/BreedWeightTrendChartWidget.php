<?php

namespace App\Filament\Widgets;

use App\Models\AnimalWeight;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BreedWeightTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Breed Weight Trends';

    protected static ?string $description = 'Average weight progression per breed';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '6';
    protected static ?string $maxHeight = '720px';
    public static function canView(): bool
{
    return auth()->user()?->can('view weight records') ?? false;
}

    protected function getFilters(): ?array
    {
        return [
            '3' => '3 Months',
            '6' => '6 Months',
            '12' => '12 Months',
        ];
    }

    protected function breedColors(): array
    {
        return [
            '#008f00',
            '#2563eb',
            '#dc2626',
            '#f59e0b',
            '#7c3aed',
            '#0891b2',
            '#16a34a',
            '#ea580c',
            '#be123c',
            '#0f766e',
        ];
    }

    protected function getData(): array
    {
        $months = (int) ($this->filter ?? 6);
        $start = now()->startOfMonth()->subMonths($months - 1);

        $records = AnimalWeight::query()
            ->join('animals', 'animal_weights.animal_id', '=', 'animals.id')
            ->join('breeds', 'animals.breed_id', '=', 'breeds.id')
            ->whereNull('animal_weights.deleted_at')
            ->where('animal_weights.recorded_at', '>=', $start)
            ->select([
                'breeds.breed_name',
                DB::raw("DATE_FORMAT(animal_weights.recorded_at, '%Y-%m') as month"),
                DB::raw('ROUND(AVG(animal_weights.weight_kg), 2) as avg_weight'),
            ])
            ->groupBy('breeds.breed_name', 'month')
            ->orderBy('month')
            ->get();

        $labels = collect(range(0, $months - 1))
            ->map(fn ($i) => $start->copy()->addMonths($i))
            ->mapWithKeys(fn ($date) => [
                $date->format('Y-m') => $date->format('M Y'),
            ]);

        $colors = $this->breedColors();

        $breeds = $records
            ->pluck('breed_name')
            ->unique()
            ->values();

        $datasets = $breeds
            ->map(function ($breed, $index) use ($records, $labels, $colors) {
                $color = $colors[$index % count($colors)];

                return [
                    'label' => $breed,
                    'data' => $labels->keys()->map(function ($month) use ($records, $breed) {
                        return (float) ($records
                            ->where('breed_name', $breed)
                            ->where('month', $month)
                            ->first()?->avg_weight ?? 0);
                    })->values()->toArray(),

                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => '#ffffff',

                    'tension' => 0.45,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                    'fill' => false,
                ];
            })
            ->values()
            ->toArray();

        return [
            'datasets' => $datasets,
            'labels' => $labels->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => 'Average Weight (KG)',
                    ],
                ],
            ],
        ];
    }
}
