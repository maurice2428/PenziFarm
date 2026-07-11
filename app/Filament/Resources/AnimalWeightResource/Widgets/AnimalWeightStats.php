<?php

namespace App\Filament\Resources\AnimalWeightResource\Widgets;

use App\Models\AnimalWeight;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class AnimalWeightStats extends Widget
{
    protected static string $view =
        'filament.resources.animal-weight-resource.widgets.animal-weight-stats';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        $latestRecords = $this->latestWeightRecords();

        $trackedAnimals = $latestRecords->count();

        $averageWeight = $trackedAnimals > 0
            ? (float) $latestRecords->avg(
                fn (AnimalWeight $record): float =>
                    (float) $record->weight_kg
            )
            : null;

        $gaining = $latestRecords
            ->filter(
                fn (AnimalWeight $record): bool =>
                    $record->previous_weight_kg !== null
                    && (float) $record->weight_kg
                        > (float) $record->previous_weight_kg
            )
            ->count();

        $losing = $latestRecords
            ->filter(
                fn (AnimalWeight $record): bool =>
                    $record->previous_weight_kg !== null
                    && (float) $record->weight_kg
                        < (float) $record->previous_weight_kg
            )
            ->count();

        $stable = $latestRecords
            ->filter(
                fn (AnimalWeight $record): bool =>
                    $record->previous_weight_kg !== null
                    && (float) $record->weight_kg
                        === (float) $record->previous_weight_kg
            )
            ->count();

        $baselineOnly = $latestRecords
            ->whereNull('previous_weight_kg')
            ->count();

        $recentEntries = AnimalWeight::query()
            ->whereNull('deleted_at')
            ->where(
                'recorded_at',
                '>=',
                now('Africa/Nairobi')->subDays(30)
            )
            ->count();

        $latestRecordedAt = $latestRecords
            ->max('recorded_at');

        $trendEligible = max(
            1,
            $trackedAnimals - $baselineOnly
        );

        $gainingRate = round(
            ($gaining / $trendEligible) * 100
        );

        $losingRate = round(
            ($losing / $trendEligible) * 100
        );

        return [
            'cards' => [
                [
                    'label' => 'Animals Tracked',
                    'value' => number_format($trackedAnimals),
                    'description' =>
                        'Animals with an active latest weight record',
                    'meta' => $baselineOnly > 0
                        ? number_format($baselineOnly)
                            . ' baseline-only'
                        : 'All have trend history',
                    'icon' => 'heroicon-o-scale',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Average Live Weight',
                    'value' => $averageWeight === null
                        ? '—'
                        : number_format($averageWeight, 2) . ' KG',
                    'description' =>
                        'Average of every animal’s latest reading',
                    'meta' => $latestRecordedAt
                        ? 'Updated '
                            . $latestRecordedAt->diffForHumans()
                        : 'No readings recorded',
                    'icon' => 'heroicon-o-chart-bar-square',
                    'tone' => 'average',
                ],
                [
                    'label' => 'Gaining Weight',
                    'value' => number_format($gaining),
                    'description' =>
                        'Latest reading is above the previous reading',
                    'meta' => $gainingRate . '% of comparable animals',
                    'icon' => 'heroicon-o-arrow-trending-up',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Losing Weight',
                    'value' => number_format($losing),
                    'description' =>
                        'Animals requiring closer health monitoring',
                    'meta' => $losingRate . '% of comparable animals',
                    'icon' => 'heroicon-o-arrow-trending-down',
                    'tone' => 'danger',
                ],
                [
                    'label' => 'Stable Weight',
                    'value' => number_format($stable),
                    'description' =>
                        'No change from the immediately previous reading',
                    'meta' => number_format($trendEligible)
                        . ' comparable animals',
                    'icon' => 'heroicon-o-minus-circle',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Recent Readings',
                    'value' => number_format($recentEntries),
                    'description' =>
                        'Weight entries recorded during the last 30 days',
                    'meta' => now('Africa/Nairobi')->format('d M Y'),
                    'icon' => 'heroicon-o-calendar-days',
                    'tone' => 'recent',
                ],
            ],

            'primaryColor' => $this->safeColor(
                setting('theme.primary', '#14532d'),
                '#14532d'
            ),

            'secondaryColor' => $this->safeColor(
                setting('theme.secondary', '#166534'),
                '#166534'
            ),

            'accentColor' => $this->safeColor(
                setting('theme.accent', '#b7791f'),
                '#b7791f'
            ),

            'successColor' => $this->safeColor(
                setting('theme.success', '#16a34a'),
                '#16a34a'
            ),

            'dangerColor' => $this->safeColor(
                setting('theme.danger', '#dc2626'),
                '#dc2626'
            ),
        ];
    }

    private function latestWeightRecords(): Collection
    {
        $latestIds = AnimalWeight::query()
            ->selectRaw('MAX(id)')
            ->whereNull('deleted_at')
            ->groupBy('animal_id');

        return AnimalWeight::query()
            ->whereIn('animal_weights.id', $latestIds)
            ->whereNull('animal_weights.deleted_at')
            ->select('animal_weights.*')
            ->selectSub(
                function ($query): void {
                    $query
                        ->from(
                            'animal_weights as previous_weights'
                        )
                        ->select('previous_weights.weight_kg')
                        ->whereColumn(
                            'previous_weights.animal_id',
                            'animal_weights.animal_id'
                        )
                        ->whereNull(
                            'previous_weights.deleted_at'
                        )
                        ->where(function ($query): void {
                            $query
                                ->whereColumn(
                                    'previous_weights.recorded_at',
                                    '<',
                                    'animal_weights.recorded_at'
                                )
                                ->orWhere(
                                    function ($query): void {
                                        $query
                                            ->whereColumn(
                                                'previous_weights.recorded_at',
                                                '=',
                                                'animal_weights.recorded_at'
                                            )
                                            ->whereColumn(
                                                'previous_weights.id',
                                                '<',
                                                'animal_weights.id'
                                            );
                                    }
                                );
                        })
                        ->orderByDesc(
                            'previous_weights.recorded_at'
                        )
                        ->orderByDesc(
                            'previous_weights.id'
                        )
                        ->limit(1);
                },
                'previous_weight_kg'
            )
            ->get();
    }

    private function safeColor(
        mixed $value,
        string $fallback
    ): string {
        $color = trim((string) $value);

        return preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $color
        )
            ? $color
            : $fallback;
    }
}
