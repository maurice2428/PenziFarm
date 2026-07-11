<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BreedingRecord;
use Illuminate\Support\Collection;

class BreedingRiskAnalyticsService
{
    public function __construct(
        private readonly ProgenyAnalyticsService $progenyAnalytics
    ) {
    }

    public function dashboard(
        string $sexFilter = 'all',
        string $recommendationFilter = 'all',
        int $minimumEvidence = 1,
        int $limit = 16
    ): array {
        $minimumEvidence = max(1, min(20, $minimumEvidence));
        $limit = max(4, min(50, $limit));

        $animals = Animal::query()
            ->with([
                'breed:id,breed_name',
                'location:id,name',
            ])
            ->withCount([
                'breedingRecordsAsFemale as breeding_records_as_female_count' =>
                    fn ($query) => $query->withTrashed(),
                'breedingRecordsAsMale as breeding_records_as_male_count' =>
                    fn ($query) => $query->withTrashed(),
                'offspringAsSire',
                'offspringAsDam',
            ])
            ->where(function ($query): void {
                $query
                    ->where('is_archived', false)
                    ->orWhereNull('is_archived');
            })
            ->whereIn('sex', ['Male', 'Female'])
            ->where(function ($query): void {
                $query
                    ->whereHas(
                        'breedingRecordsAsFemale',
                        fn ($records) => $records->withTrashed()
                    )
                    ->orWhereHas(
                        'breedingRecordsAsMale',
                        fn ($records) => $records->withTrashed()
                    )
                    ->orWhereHas('offspringAsSire')
                    ->orWhereHas('offspringAsDam');
            })
            ->when(
                in_array($sexFilter, ['Male', 'Female'], true),
                fn ($query) => $query->where('sex', $sexFilter)
            )
            ->orderBy('tag_number')
            ->limit(350)
            ->get();

        $items = $animals
            ->map(function (Animal $animal) use (
                $minimumEvidence
            ): array {
                $metrics = $this->progenyAnalytics
                    ->metrics($animal);

                $isFemale = $animal->sex === 'Female';

                $evidence = $isFemale
                    ? (int) (
                        $metrics['services']
                        ?? $animal
                            ->breeding_records_as_female_count
                    )
                    : max(
                        (int) (
                            $metrics['direct_offspring']
                            ?? $animal->offspring_as_sire_count
                        ),
                        (int) $animal
                            ->breeding_records_as_male_count
                    );

                $recommendation = $evidence < $minimumEvidence
                    ? 'insufficient_data'
                    : (string) (
                        $metrics['recommendation']
                        ?? 'insufficient_data'
                    );

                $survivalRate = $isFemale
                    ? (float) (
                        $metrics['live_birth_survival_rate']
                        ?? 0
                    )
                    : (float) (
                        $metrics['survival_rate']
                        ?? 0
                    );

                $historyCount = $isFemale
                    ? (int) $animal
                        ->breeding_records_as_female_count
                    : (int) $animal
                        ->breeding_records_as_male_count;

                return [
                    'animal' => $animal,
                    'metrics' => $metrics,
                    'score' => round(
                        (float) ($metrics['score'] ?? 0),
                        2
                    ),
                    'recommendation' => $recommendation,
                    'evidence' => $evidence,
                    'history_count' => $historyCount,
                    'survival_rate' => round(
                        $survivalRate,
                        2
                    ),
                    'role' => $isFemale ? 'dam' : 'sire',
                    'risk_flags' => $this->riskFlags(
                        $animal,
                        $metrics
                    ),
                ];
            })
            ->filter(
                fn (array $item): bool =>
                    $recommendationFilter === 'all'
                    || $item['recommendation']
                        === $recommendationFilter
            )
            ->sort(function (
                array $left,
                array $right
            ): int {
                $leftInsufficient =
                    $left['recommendation']
                    === 'insufficient_data';

                $rightInsufficient =
                    $right['recommendation']
                    === 'insufficient_data';

                if ($leftInsufficient !== $rightInsufficient) {
                    return $leftInsufficient ? 1 : -1;
                }

                return $left['score']
                    <=> $right['score'];
            })
            ->values();

        $ranked = $items
            ->reject(
                fn (array $item): bool =>
                    $item['recommendation']
                    === 'insufficient_data'
            )
            ->values();

        $insufficient = $items
            ->where(
                'recommendation',
                'insufficient_data'
            )
            ->values();

        return [
            'summary' => [
                'evaluated' => $ranked->count(),
                'cull' => $ranked
                    ->where('recommendation', 'cull')
                    ->count(),
                'sell' => $ranked
                    ->where('recommendation', 'sell')
                    ->count(),
                'monitor' => $ranked
                    ->where('recommendation', 'monitor')
                    ->count(),
                'retain' => $ranked
                    ->where('recommendation', 'retain')
                    ->count(),
                'insufficient' => $insufficient->count(),
                'average_score' => $ranked->isNotEmpty()
                    ? round(
                        (float) $ranked->avg('score'),
                        2
                    )
                    : 0,
            ],
            'lowest' => $ranked
                ->take($limit)
                ->values(),
            'lowest_dams' => $ranked
                ->where('role', 'dam')
                ->take($limit)
                ->values(),
            'lowest_sires' => $ranked
                ->where('role', 'sire')
                ->take($limit)
                ->values(),
            'insufficient' => $insufficient
                ->take($limit)
                ->values(),
            'all_items' => $items,
        ];
    }

    public function history(Animal $animal): array
    {
        $records = BreedingRecord::withTrashed()
            ->with([
                'batch' => fn ($query) =>
                    $query->withTrashed(),
                'female.breed:id,breed_name',
                'male.breed:id,breed_name',
                'offspring.breed:id,breed_name',
                'offspring.location:id,name',
            ])
            ->when(
                $animal->sex === 'Female',
                fn ($query) => $query->where(
                    'female_animal_id',
                    $animal->id
                ),
                fn ($query) => $query->where(
                    'male_animal_id',
                    $animal->id
                )
            )
            ->orderByDesc('mating_date')
            ->orderByDesc('id')
            ->get();

        $items = $records
            ->map(function (BreedingRecord $record): array {
                $offspring = $record->offspring
                    ->map(function (Animal $animal): array {
                        return [
                            'id' => $animal->id,
                            'tag_number' =>
                                $animal->tag_number,
                            'sex' => $animal->sex,
                            'breed' =>
                                $animal->breed?->breed_name,
                            'date_of_birth' =>
                                $animal->date_of_birth
                                    ?->format('d M Y'),
                            'status' => $animal->status,
                            'purpose' => $animal->purpose,
                            'surviving' =>
                                $animal->status !== 'Dead',
                            'is_breeder' =>
                                (bool) $animal->is_breeder,
                            'purity' =>
                                $animal->breed_purity_percent !== null
                                    ? (float) $animal
                                        ->breed_purity_percent
                                    : null,
                            'location' =>
                                $animal->location?->name,
                            'date_died' =>
                                $animal->date_died
                                    ?->format('d M Y'),
                            'cause_of_death' =>
                                $animal->cause_of_death,
                            'date_culled' =>
                                $animal->date_culled
                                    ?->format('d M Y'),
                            'culling_reason' =>
                                $animal->culling_reason,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $record->id,
                    'archived' =>
                        $record->trashed()
                        || $record->batch?->trashed(),
                    'batch_id' =>
                        $record->breeding_batch_id,
                    'batch_number' =>
                        $record->batch?->batch_number
                        ?? 'Legacy / missing batch',
                    'batch_name' =>
                        $record->batch?->name
                        ?? 'Breeding record',
                    'batch_status' =>
                        $record->batch?->status,
                    'batch_notes' =>
                        $record->batch?->notes,
                    'species' =>
                        $record->species,
                    'breeding_type' =>
                        $record->breeding_type
                        ?: 'natural',
                    'is_cross_breed' =>
                        (bool) $record->is_cross_breed,
                    'gestation_days' =>
                        (int) $record->gestation_days,
                    'inbreeding_status' =>
                        $record->inbreeding_status
                        ?: 'clear',
                    'relationship_notes' =>
                        $record->relationship_notes,
                    'sire' =>
                        $record->male?->tag_number
                        ?? 'Unknown sire',
                    'sire_id' =>
                        $record->male_animal_id,
                    'dam' =>
                        $record->female?->tag_number
                        ?? 'Unknown dam',
                    'dam_id' =>
                        $record->female_animal_id,
                    'mating_date' =>
                        $record->mating_date
                            ?->format('d M Y'),
                    'pregnancy_checked_at' =>
                        $record->pregnancy_checked_at
                            ?->format('d M Y'),
                    'expected_due_date' =>
                        $record->expected_due_date
                            ?->format('d M Y'),
                    'delivery_date' =>
                        $record->delivery_date
                            ?->format('d M Y'),
                    'pregnancy_status' =>
                        $record->pregnancy_status
                        ?: 'pending',
                    'birth_outcome' =>
                        $record->birth_outcome
                        ?: 'pending',
                    'birth_assistance' =>
                        $record->birth_assistance,
                    'offspring_count' =>
                        (int) $record->offspring_count,
                    'live_birth_count' =>
                        (int) $record->live_birth_count,
                    'stillborn_count' =>
                        (int) $record->stillborn_count,
                    'neonatal_death_count' =>
                        (int) $record
                            ->neonatal_death_count,
                    'weaned_count' =>
                        (int) $record->weaned_count,
                    'retained_breeding_count' =>
                        (int) $record
                            ->retained_breeding_count,
                    'mothering_score' =>
                        $record->mothering_score !== null
                            ? (float) $record
                                ->mothering_score
                            : null,
                    'milk_score' =>
                        $record->milk_score !== null
                            ? (float) $record->milk_score
                            : null,
                    'offspring_vigour_score' =>
                        $record
                            ->offspring_vigour_score
                            !== null
                                ? (float) $record
                                    ->offspring_vigour_score
                                : null,
                    'temperament_score' =>
                        $record->temperament_score !== null
                            ? (float) $record
                                ->temperament_score
                            : null,
                    'evaluation_completed_at' =>
                        $record->evaluation_completed_at
                            ?->format('d M Y, H:i'),
                    'delivery_notes' =>
                        $record->delivery_notes,
                    'maternal_notes' =>
                        $record->maternal_notes,
                    'notes' =>
                        $record->notes,
                    'created_at' =>
                        $record->created_at
                            ?->format('d M Y, H:i'),
                    'updated_at' =>
                        $record->updated_at
                            ?->format('d M Y, H:i'),
                    'offspring' => $offspring,
                    'risk_flags' =>
                        $this->recordRiskFlags($record),
                    'events' => $this->recordEvents(
                        $record
                    ),
                ];
            })
            ->values();

        return [
            'records' => $items,
            'total_records' => $items->count(),
            'archived_records' => $items
                ->where('archived', true)
                ->count(),
            'registered_offspring' => $items
                ->sum(
                    fn (array $item): int =>
                        count($item['offspring'])
                ),
        ];
    }

    public function animalSnapshot(Animal $animal): array
    {
        $metrics = $this->progenyAnalytics
            ->metrics($animal);

        $history = $this->history($animal);

        return [
            'animal' => $animal->loadMissing([
                'breed:id,breed_name',
                'location:id,name',
                'sire:id,tag_number',
                'dam:id,tag_number',
            ]),
            'metrics' => $metrics,
            'history' => $history,
            'risk_flags' => $this->riskFlags(
                $animal,
                $metrics
            ),
        ];
    }

    private function riskFlags(
        Animal $animal,
        array $metrics
    ): array {
        $flags = [];

        if ($animal->sex === 'Female') {
            if ((int) ($metrics['abortions'] ?? 0) > 0) {
                $flags[] =
                    number_format(
                        (int) $metrics['abortions']
                    )
                    . ' abortion(s)';
            }

            if (
                (int) (
                    $metrics['not_pregnant']
                    ?? 0
                ) > 0
            ) {
                $flags[] =
                    number_format(
                        (int) $metrics['not_pregnant']
                    )
                    . ' not-pregnant result(s)';
            }

            if (
                (float) (
                    $metrics['live_birth_survival_rate']
                    ?? 100
                ) < 70
            ) {
                $flags[] =
                    'Low live-birth survival';
            }

            if (
                (float) (
                    $metrics['mothering_score']
                    ?? 5
                ) > 0
                && (float) (
                    $metrics['mothering_score']
                    ?? 5
                ) < 3
            ) {
                $flags[] =
                    'Low mothering score';
            }
        } else {
            if (
                (float) (
                    $metrics['survival_rate']
                    ?? 100
                ) < 70
            ) {
                $flags[] =
                    'Low offspring survival';
            }

            if (
                (float) (
                    $metrics[
                        'breeder_conversion_rate'
                    ] ?? 100
                ) < 25
            ) {
                $flags[] =
                    'Low breeder conversion';
            }
        }

        if ($flags === []) {
            $flags[] = 'No critical risk flag';
        }

        return $flags;
    }

    private function recordRiskFlags(
        BreedingRecord $record
    ): array {
        $flags = [];

        if (
            $record->pregnancy_status === 'aborted'
            || $record->birth_outcome === 'aborted'
        ) {
            $flags[] = 'Abortion';
        }

        if (
            $record->pregnancy_status
            === 'not_pregnant'
        ) {
            $flags[] = 'Not pregnant';
        }

        if ((int) $record->stillborn_count > 0) {
            $flags[] =
                number_format(
                    (int) $record->stillborn_count
                )
                . ' stillborn';
        }

        if (
            (int) $record->neonatal_death_count > 0
        ) {
            $flags[] =
                number_format(
                    (int) $record
                        ->neonatal_death_count
                )
                . ' neonatal death(s)';
        }

        if (
            $record->mothering_score !== null
            && (float) $record->mothering_score < 3
        ) {
            $flags[] = 'Low mothering';
        }

        return $flags;
    }

    private function recordEvents(
        BreedingRecord $record
    ): array {
        $events = [];

        if ($record->mating_date) {
            $events[] = [
                'date' => $record->mating_date
                    ->format('d M Y'),
                'timestamp' => $record->mating_date
                    ->timestamp,
                'type' => 'mating',
                'title' => 'Mating recorded',
                'detail' =>
                    'Sire '
                    . ($record->male?->tag_number
                        ?? 'unknown')
                    . ' × Dam '
                    . ($record->female?->tag_number
                        ?? 'unknown'),
            ];
        }

        if ($record->expected_due_date) {
            $events[] = [
                'date' => $record->expected_due_date
                    ->format('d M Y'),
                'timestamp' => $record->expected_due_date
                    ->timestamp,
                'type' => 'due',
                'title' => 'Expected due date',
                'detail' => 'Stored gestation target for this breeding case.',
            ];
        }

        if ($record->pregnancy_checked_at) {
            $events[] = [
                'date' => $record
                    ->pregnancy_checked_at
                    ->format('d M Y'),
                'timestamp' => $record
                    ->pregnancy_checked_at
                    ->timestamp,
                'type' => 'pregnancy',
                'title' => 'Pregnancy assessment',
                'detail' => str(
                    $record->pregnancy_status
                    ?: 'pending'
                )
                    ->replace('_', ' ')
                    ->title()
                    ->toString(),
            ];
        }

        if (
            ! $record->pregnancy_checked_at
            && in_array(
                $record->pregnancy_status,
                ['aborted', 'not_pregnant'],
                true
            )
        ) {
            $eventDate = $record->updated_at ?: $record->created_at;

            if ($eventDate) {
                $events[] = [
                    'date' => $eventDate->format('d M Y'),
                    'timestamp' => $eventDate->timestamp,
                    'type' => 'outcome',
                    'title' => 'Breeding case outcome',
                    'detail' => str($record->pregnancy_status)
                        ->replace('_', ' ')
                        ->title()
                        ->toString(),
                ];
            }
        }

        if ($record->delivery_date) {
            $events[] = [
                'date' => $record->delivery_date
                    ->format('d M Y'),
                'timestamp' => $record->delivery_date
                    ->timestamp,
                'type' => 'delivery',
                'title' => 'Delivery outcome',
                'detail' =>
                    number_format(
                        (int) $record->live_birth_count
                    )
                    . ' live, '
                    . number_format(
                        (int) $record->stillborn_count
                    )
                    . ' stillborn',
            ];
        }

        return collect($events)
            ->sortBy('timestamp')
            ->values()
            ->all();
    }
}
