<?php

namespace App\Filament\Resources\AnimalWeightResource\Pages;

use App\Filament\Resources\AnimalResource;
use App\Filament\Resources\AnimalWeightResource;
use App\Models\AnimalWeight;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ViewAnimalWeight extends ViewRecord
{
    protected static string $resource = AnimalWeightResource::class;

    protected static string $view =
        'filament.resources.animal-weight-resource.pages.view-animal-weight';

    protected function resolveRecord(int|string $key): Model
    {
        return AnimalWeight::query()
            ->with([
                'animal.breed',
                'animal.location',
                'recorder',
            ])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openAnimalProfile')
                ->label('Open Animal Record')
                ->icon('heroicon-o-identification')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        filled($this->getRecord()->animal_id)
                        && (auth()->user()?->can('view animals') ?? false)
                )
                ->url(
                    fn (): string => AnimalResource::getUrl('profile', [
                        'record' => $this->getRecord()->animal_id,
                    ])
                )
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->label('Edit Weight')
                ->icon('heroicon-o-pencil-square')
                ->visible(
                    fn (): bool =>
                        ! $this->getRecord()->trashed()
                        && (auth()->user()?->can('edit weight records') ?? false)
                ),

            Actions\Action::make('backToWeights')
                ->label('Back to Weights')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AnimalWeightResource::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        /** @var AnimalWeight $weight */
        $weight = $this->getRecord();

        return ($weight->animal?->tag_number ?? 'Animal')
            . ' · Complete Weight History';
    }

    protected function getViewData(): array
    {
        /** @var AnimalWeight $selectedRecord */
        $selectedRecord = $this->getRecord();

        $history = AnimalWeight::query()
            ->with('recorder')
            ->where('animal_id', $selectedRecord->animal_id)
            ->whereNull('deleted_at')
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $history = $this->decorateHistory($history);

        $latest = $history->last();
        $first = $history->first();

        $latestWeight = $latest
            ? (float) $latest->weight_kg
            : null;

        $firstWeight = $first
            ? (float) $first->weight_kg
            : null;

        $totalChange = (
            $latestWeight !== null
            && $firstWeight !== null
        )
            ? $latestWeight - $firstWeight
            : null;

        $averageWeight = $history->isNotEmpty()
            ? (float) $history->avg(
                fn (AnimalWeight $weight): float =>
                    (float) $weight->weight_kg
            )
            : null;

        $minimumWeight = $history->isNotEmpty()
            ? (float) $history->min('weight_kg')
            : null;

        $maximumWeight = $history->isNotEmpty()
            ? (float) $history->max('weight_kg')
            : null;

        $daysCovered = (
            $first?->recorded_at
            && $latest?->recorded_at
        )
            ? max(
                0,
                Carbon::parse($first->recorded_at)
                    ->diffInDays(Carbon::parse($latest->recorded_at))
            )
            : 0;

        $averageDailyGain = (
            $totalChange !== null
            && $daysCovered > 0
        )
            ? $totalChange / $daysCovered
            : null;

        $latestDifference = $latest?->calculated_difference;

        $summary = [
            'record_count' => $history->count(),
            'latest_weight' => $latestWeight,
            'first_weight' => $firstWeight,
            'total_change' => $totalChange,
            'average_weight' => $averageWeight,
            'minimum_weight' => $minimumWeight,
            'maximum_weight' => $maximumWeight,
            'days_covered' => $daysCovered,
            'average_daily_gain' => $averageDailyGain,
            'latest_difference' => $latestDifference,
            'latest_trend' => $latest?->calculated_trend ?? 'none',
        ];

        return [
            'selectedRecord' => $selectedRecord,
            'animal' => $selectedRecord->animal,
            'weightHistory' => $history->reverse()->values(),
            'chartHistory' => $history,
            'chart' => $this->buildChart($history),
            'summary' => $summary,
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
            'farmName' => setting('farm.name', 'Penzi Farm Limited'),
            'farmTagline' => setting(
                'farm.tagline',
                'Nurturing Quality, Inspiring Global Standards'
            ),
        ];
    }

    private function decorateHistory(Collection $history): Collection
    {
        $previousWeight = null;

        return $history->map(function (
            AnimalWeight $record
        ) use (&$previousWeight): AnimalWeight {
            $currentWeight = (float) $record->weight_kg;

            $difference = $previousWeight === null
                ? null
                : $currentWeight - $previousWeight;

            $trend = match (true) {
                $difference === null => 'first',
                $difference > 0 => 'gaining',
                $difference < 0 => 'losing',
                default => 'stable',
            };

            $record->setAttribute(
                'calculated_previous_weight',
                $previousWeight
            );

            $record->setAttribute(
                'calculated_difference',
                $difference
            );

            $record->setAttribute(
                'calculated_trend',
                $trend
            );

            $previousWeight = $currentWeight;

            return $record;
        });
    }

    private function buildChart(Collection $history): array
    {
        $width = 1100;
        $height = 360;
        $paddingLeft = 74;
        $paddingRight = 32;
        $paddingTop = 30;
        $paddingBottom = 62;

        if ($history->isEmpty()) {
            return [
                'width' => $width,
                'height' => $height,
                'polyline' => '',
                'area' => '',
                'points' => [],
                'y_ticks' => [],
                'x_labels' => [],
            ];
        }

        $weights = $history
            ->map(
                fn (AnimalWeight $record): float =>
                    (float) $record->weight_kg
            )
            ->values();

        $minimum = (float) $weights->min();
        $maximum = (float) $weights->max();

        $spread = max(1.0, $maximum - $minimum);
        $chartMin = max(0, $minimum - ($spread * 0.12));
        $chartMax = $maximum + ($spread * 0.12);
        $chartRange = max(1.0, $chartMax - $chartMin);

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $count = $history->count();

        $points = [];

        foreach ($history->values() as $index => $record) {
            $x = $count === 1
                ? $paddingLeft + ($plotWidth / 2)
                : $paddingLeft
                    + (($plotWidth * $index) / ($count - 1));

            $normalised = (
                ((float) $record->weight_kg - $chartMin)
                / $chartRange
            );

            $y = $paddingTop
                + $plotHeight
                - ($normalised * $plotHeight);

            $points[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'weight' => (float) $record->weight_kg,
                'date' => Carbon::parse($record->recorded_at)
                    ->format('d M Y'),
                'trend' => $record->calculated_trend,
            ];
        }

        $polyline = collect($points)
            ->map(
                fn (array $point): string =>
                    $point['x'] . ',' . $point['y']
            )
            ->implode(' ');

        $baseline = $paddingTop + $plotHeight;

        $area = $polyline !== ''
            ? $paddingLeft . ',' . $baseline
                . ' ' . $polyline
                . ' ' . ($paddingLeft + $plotWidth)
                . ',' . $baseline
            : '';

        $yTicks = collect(range(0, 4))
            ->map(function (int $index) use (
                $chartMax,
                $chartRange,
                $paddingTop,
                $plotHeight
            ): array {
                $ratio = $index / 4;

                return [
                    'value' => $chartMax - ($chartRange * $ratio),
                    'y' => $paddingTop + ($plotHeight * $ratio),
                ];
            })
            ->all();

        $labelStep = max(1, (int) ceil($count / 7));

        $xLabels = [];

        foreach ($history->values() as $index => $record) {
            if (
                $index % $labelStep !== 0
                && $index !== $count - 1
            ) {
                continue;
            }

            $xLabels[] = [
                'x' => $points[$index]['x'],
                'label' => Carbon::parse($record->recorded_at)
                    ->format('d M'),
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'polyline' => $polyline,
            'area' => $area,
            'points' => $points,
            'y_ticks' => $yTicks,
            'x_labels' => $xLabels,
            'plot' => [
                'left' => $paddingLeft,
                'right' => $width - $paddingRight,
                'top' => $paddingTop,
                'bottom' => $baseline,
            ],
        ];
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
