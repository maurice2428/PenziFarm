<?php

namespace App\Filament\Widgets;

use App\Models\AnimalWeight;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BreedCurrentAverageWeightChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Current Breed Average Weight';

    protected static ?string $description = 'Latest average weight per breed';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '6000px';
    public static function canView(): bool
{
    return auth()->user()?->can('view weight records') ?? false;
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
        $records = AnimalWeight::query()
            ->join('animals', 'animal_weights.animal_id', '=', 'animals.id')
            ->join('breeds', 'animals.breed_id', '=', 'breeds.id')
            ->whereNull('animal_weights.deleted_at')
            ->whereIn('animal_weights.id', function ($query) {
                $query
                    ->selectRaw('MAX(id)')
                    ->from('animal_weights')
                    ->whereNull('deleted_at')
                    ->groupBy('animal_id');
            })
            ->select([
                'breeds.id as breed_id',
                'breeds.breed_name',
                DB::raw('ROUND(AVG(animal_weights.weight_kg), 2) as avg_weight'),
            ])
            ->groupBy('breeds.id', 'breeds.breed_name')
            ->orderByDesc('avg_weight')
            ->get();

        $colors = $this->breedColors();

        return [
            'datasets' => [
                [
                    'label' => 'Average Weight (KG)',
                    'data' => $records
                        ->pluck('avg_weight')
                        ->map(fn($value) => (float) $value)
                        ->values()
                        ->toArray(),
                    'backgroundColor' => $records
                        ->values()
                        ->map(fn($record, $index) => $colors[$index % count($colors)])
                        ->toArray(),
                    'borderColor' => $records
                        ->values()
                        ->map(fn($record, $index) => $colors[$index % count($colors)])
                        ->toArray(),
                    'borderWidth' => 0,
                    'borderRadius' => 10,
                    'barThickness' => 36,
                    'maxBarThickness' => 46,
                ],
            ],
            'labels' => $records
                ->pluck('breed_name')
                ->values()
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
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
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Average Weight (KG)',
                    ],
                ],
            ],
        ];
    }
}
