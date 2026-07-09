<?php

namespace App\Filament\Resources\AnimalWeightResource\Widgets;

use App\Models\AnimalWeight;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AnimalWeightBreedStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $losingAnimals = AnimalWeight::query()
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

        $gainingAnimals = AnimalWeight::query()
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('animal_weights')
                    ->whereNull('deleted_at')
                    ->groupBy('animal_id');
            })
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

        $bestBreed = DB::table('animal_weights')
            ->join('animals', 'animal_weights.animal_id', '=', 'animals.id')
            ->join('breeds', 'animals.breed_id', '=', 'breeds.id')
            ->whereNull('animal_weights.deleted_at')
            ->select('breeds.breed_name', DB::raw('AVG(animal_weights.weight_kg) as avg_weight'))
            ->groupBy('breeds.breed_name')
            ->orderByDesc('avg_weight')
            ->first();

        return [
            Stat::make('Gaining Animals', number_format($gainingAnimals))
                ->description('Animals improving from previous weight')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Animals To Attend To', number_format($losingAnimals))
                ->description('Latest record shows weight loss')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Best Average Breed', $bestBreed?->breed_name ?? 'N/A')
                ->description($bestBreed ? number_format((float) $bestBreed->avg_weight, 2) . ' KG average' : 'No breed weight data')
                ->icon('heroicon-o-trophy')
                ->color('warning'),
        ];
    }
}
