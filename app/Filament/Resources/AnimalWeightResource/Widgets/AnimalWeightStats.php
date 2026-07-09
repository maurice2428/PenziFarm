<?php

namespace App\Filament\Resources\AnimalWeightResource\Widgets;

use App\Models\Animal;
use App\Models\AnimalWeight;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AnimalWeightStats extends StatsOverviewWidget
{

/*protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 4,
            'xl' => 4,
        ];
    }
    */
    protected function getColumns(): int
{
    return 4;
}

    protected function getStats(): array
    {
        $totalAnimals = Animal::query()
            ->where('is_archived', false)
            ->count();

        $totalRecords = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->count();

        $todayRecords = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->whereDate('recorded_at', today())
            ->count();

        $animalsTracked = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->distinct('animal_id')
            ->count('animal_id');

        $missingWeights = max($totalAnimals - $animalsTracked, 0);

        $coverage = $totalAnimals > 0
            ? round(($animalsTracked / $totalAnimals) * 100)
            : 0;

        $latestAverage = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('animal_weights')
                    ->whereNull('deleted_at')
                    ->groupBy('animal_id');
            })
            ->avg('weight_kg');

        $latestIdsSubQuery = AnimalWeight::query()
            ->selectRaw('MAX(id)')
            ->whereNull('deleted_at')
            ->groupBy('animal_id');

        $gainingAnimals = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->whereIn('id', $latestIdsSubQuery)
            ->whereRaw("
                weight_kg > (
                    SELECT aw2.weight_kg
                    FROM animal_weights aw2
                    WHERE aw2.animal_id = animal_weights.animal_id
                    AND aw2.recorded_at < animal_weights.recorded_at
                    AND aw2.deleted_at IS NULL
                    ORDER BY aw2.recorded_at DESC
                    LIMIT 1
                )
            ")
            ->count();

        $losingAnimals = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('animal_weights')
                    ->whereNull('deleted_at')
                    ->groupBy('animal_id');
            })
            ->whereRaw("
                weight_kg < (
                    SELECT aw2.weight_kg
                    FROM animal_weights aw2
                    WHERE aw2.animal_id = animal_weights.animal_id
                    AND aw2.recorded_at < animal_weights.recorded_at
                    AND aw2.deleted_at IS NULL
                    ORDER BY aw2.recorded_at DESC
                    LIMIT 1
                )
            ")
            ->count();

        $bestBreed = DB::table('animal_weights')
            ->join('animals', 'animal_weights.animal_id', '=', 'animals.id')
            ->join('breeds', 'animals.breed_id', '=', 'breeds.id')
            ->whereNull('animal_weights.deleted_at')
            ->where('animals.is_archived', false)
            ->select('breeds.breed_name', DB::raw('AVG(animal_weights.weight_kg) as avg_weight'))
            ->groupBy('breeds.breed_name')
            ->orderByDesc('avg_weight')
            ->first();

        $compact = [
            'class' => 'border-l-4 shadow-sm rounded-2xl py-2 px-3 min-h-[92px]',
        ];

        return [
            Stat::make('Animals Tracked', number_format($animalsTracked) . ' of ' . number_format($totalAnimals))
                ->description($coverage . '% coverage of herd')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->icon('heroicon-o-scale')
                ->color('success')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-green-600 bg-gradient-to-br from-white to-green-50',
                ]),

            Stat::make('Missing Weights', number_format($missingWeights))
                ->description('Animals with no weight records')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($missingWeights > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-red-600 bg-gradient-to-br from-white to-red-50',
                ]),

            Stat::make('Total Records', number_format($totalRecords))
                ->description('Active historical weight entries')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-blue-600 bg-gradient-to-br from-white to-blue-50',
                ]),

            Stat::make('Average Latest Weight', number_format((float) $latestAverage, 2) . ' KG')
                ->description('Latest record per animal')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->icon('heroicon-o-chart-bar')
                ->color('warning')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-amber-500 bg-gradient-to-br from-white to-amber-50',
                ]),

            Stat::make('Recorded Today', number_format($todayRecords))
                ->description('Today’s weight entries')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-indigo-600 bg-gradient-to-br from-white to-indigo-50',
                ]),

            Stat::make('Gaining Animals', number_format($gainingAnimals))
                ->description('Latest record shows improvement')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-emerald-600 bg-gradient-to-br from-white to-emerald-50',
                ]),

            Stat::make('Animals To Attend To', number_format($losingAnimals))
                ->description('Latest record shows weight loss')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-rose-600 bg-gradient-to-br from-white to-rose-50',
                ]),

            Stat::make('Best Average Breed', $bestBreed?->breed_name ?? 'N/A')
                ->description($bestBreed ? number_format((float) $bestBreed->avg_weight, 2) . ' KG average' : 'No breed data')
                ->descriptionIcon('heroicon-m-trophy')
                ->icon('heroicon-o-trophy')
                ->color('warning')
                ->extraAttributes([
                    'class' => $compact['class'] . ' border-l-yellow-500 bg-gradient-to-br from-white to-yellow-50',
                ]),
        ];
    }
}
