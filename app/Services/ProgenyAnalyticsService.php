<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalBreedingReview;
use App\Models\BreedingRecord;
use Illuminate\Support\Collection;

class ProgenyAnalyticsService
{
    public function tree(Animal $animal, int $generations = 3, string $mode = 'descendants'): array
    {
        $generations = max(1, min(5, $generations));
        $visited = [];

        return $mode === 'ancestors'
            ? $this->buildAncestorNode($animal, 0, $generations, $visited)
            : $this->buildDescendantNode($animal, 0, $generations, $visited);
    }

    public function metrics(Animal $animal): array
    {
        return $animal->sex === 'Female'
            ? $this->damMetrics($animal)
            : $this->sireMetrics($animal);
    }

    public function sireMetrics(Animal $animal): array
    {
        $direct = Animal::query()
            ->with(['breed:id,breed_name', 'location:id,name'])
            ->where('sire_id', $animal->id)
            ->get();

        $allDescendants = $this->flattenTree(
            $this->tree($animal, 5, 'descendants')
        )->reject(fn (array $node): bool => (int) $node['id'] === (int) $animal->id);

        $directCount = $direct->count();
        $surviving = $direct->where('status', '!=', 'Dead')->count();
        $breederOffspring = $direct->where('is_breeder', true)->count();
        $active = $direct->where('status', 'Active')->count();
        $maleOffspring = $direct->where('sex', 'Male')->count();
        $femaleOffspring = $direct->where('sex', 'Female')->count();
        $purityValues = $direct
            ->pluck('breed_purity_percent')
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): float => (float) $value);

        $survivalRate = $directCount > 0 ? ($surviving / $directCount) * 100 : 0;
        $breederConversion = $directCount > 0 ? ($breederOffspring / $directCount) * 100 : 0;
        $averagePurity = $purityValues->isNotEmpty() ? (float) $purityValues->avg() : 0;

        $score = min(100, max(0,
            ($survivalRate * 0.40)
            + ($breederConversion * 0.25)
            + ($averagePurity * 0.20)
            + min(15, $directCount * 2)
        ));

        $recommendation = match (true) {
            $directCount === 0 => 'insufficient_data',
            $score >= 80 => 'retain',
            $score >= 65 => 'monitor',
            default => 'sell',
        };

        return [
            'role' => 'sire',
            'score' => round($score, 2),
            'recommendation' => $recommendation,
            'direct_offspring' => $directCount,
            'all_descendants' => $allDescendants->count(),
            'active_offspring' => $active,
            'surviving_offspring' => $surviving,
            'breeder_offspring' => $breederOffspring,
            'male_offspring' => $maleOffspring,
            'female_offspring' => $femaleOffspring,
            'survival_rate' => round($survivalRate, 2),
            'breeder_conversion_rate' => round($breederConversion, 2),
            'average_offspring_purity' => round($averagePurity, 2),
            'reason' => $this->sireReason($directCount, $score, $survivalRate, $breederConversion),
        ];
    }

    public function damMetrics(Animal $animal): array
    {
        $records = BreedingRecord::withTrashed()
            ->where('female_animal_id', $animal->id)
            ->orderBy('mating_date')
            ->get();

        $services = $records->count();
        $confirmed = $records->whereIn('pregnancy_status', ['confirmed', 'delivered', 'aborted'])->count();
        $delivered = $records->filter(
            fn (BreedingRecord $record): bool =>
                $record->pregnancy_status === 'delivered'
                || in_array($record->birth_outcome, ['live_birth', 'stillbirth', 'mixed'], true)
        )->count();
        $abortions = $records->filter(
            fn (BreedingRecord $record): bool =>
                $record->pregnancy_status === 'aborted'
                || $record->birth_outcome === 'aborted'
        )->count();
        $notPregnant = $records->where('pregnancy_status', 'not_pregnant')->count();

        $liveBirths = (int) $records->sum('live_birth_count');
        $stillborn = (int) $records->sum('stillborn_count');
        $neonatalDeaths = (int) $records->sum('neonatal_death_count');
        $weaned = (int) $records->sum('weaned_count');
        $retained = (int) $records->sum('retained_breeding_count');

        $mothering = $this->averageScore($records, 'mothering_score');
        $milk = $this->averageScore($records, 'milk_score');
        $temperament = $this->averageScore($records, 'temperament_score');
        $vigour = $this->averageScore($records, 'offspring_vigour_score');

        $conceptionRate = $services > 0 ? ($confirmed / $services) * 100 : 0;
        $deliveryRate = $services > 0 ? ($delivered / $services) * 100 : 0;
        $abortionRate = $confirmed > 0 ? ($abortions / $confirmed) * 100 : 0;
        $liveBirthSurvivalRate = $liveBirths > 0
            ? max(0, (($liveBirths - $neonatalDeaths) / $liveBirths) * 100)
            : 0;
        $weaningRate = $liveBirths > 0 ? ($weaned / $liveBirths) * 100 : 0;

        $score = min(100, max(0,
            ($conceptionRate * 0.22)
            + ($deliveryRate * 0.18)
            + ($liveBirthSurvivalRate * 0.20)
            + ($weaningRate * 0.15)
            + (($mothering / 5) * 12)
            + (($milk / 5) * 5)
            + (($temperament / 5) * 3)
            + (($vigour / 5) * 5)
            - ($abortions * 8)
            - ($stillborn * 4)
            - ($neonatalDeaths * 3)
        ));

        $recommendation = $this->damRecommendation(
            $services,
            $abortions,
            $notPregnant,
            $mothering,
            $score,
            $conceptionRate
        );

        return [
            'role' => 'dam',
            'score' => round($score, 2),
            'recommendation' => $recommendation,
            'services' => $services,
            'confirmed_pregnancies' => $confirmed,
            'deliveries' => $delivered,
            'abortions' => $abortions,
            'not_pregnant' => $notPregnant,
            'live_births' => $liveBirths,
            'stillborn' => $stillborn,
            'neonatal_deaths' => $neonatalDeaths,
            'weaned' => $weaned,
            'retained_breeding_offspring' => $retained,
            'mothering_score' => round($mothering, 2),
            'milk_score' => round($milk, 2),
            'temperament_score' => round($temperament, 2),
            'offspring_vigour_score' => round($vigour, 2),
            'conception_rate' => round($conceptionRate, 2),
            'delivery_rate' => round($deliveryRate, 2),
            'abortion_rate' => round($abortionRate, 2),
            'live_birth_survival_rate' => round($liveBirthSurvivalRate, 2),
            'weaning_rate' => round($weaningRate, 2),
            'reason' => $this->damReason(
                $services,
                $abortions,
                $notPregnant,
                $mothering,
                $score,
                $conceptionRate
            ),
        ];
    }

    public function topSires(int $limit = 5): Collection
    {
        return Animal::query()
            ->with(['breed:id,breed_name'])
            ->withCount('offspringAsSire')
            ->where('sex', 'Male')
            ->where('is_archived', false)
            ->having('offspring_as_sire_count', '>', 0)
            ->orderByDesc('offspring_as_sire_count')
            ->limit(max($limit * 3, 12))
            ->get()
            ->map(function (Animal $animal): array {
                return [
                    'animal' => $animal,
                    'metrics' => $this->sireMetrics($animal),
                ];
            })
            ->sortByDesc(fn (array $item): float => (float) $item['metrics']['score'])
            ->take($limit)
            ->values();
    }

    public function topDams(int $limit = 5): Collection
    {
        return Animal::query()
            ->with(['breed:id,breed_name'])
            ->withCount(['offspringAsDam', 'breedingRecordsAsFemale'])
            ->where('sex', 'Female')
            ->where('is_archived', false)
            ->having('breeding_records_as_female_count', '>', 0)
            ->orderByDesc('offspring_as_dam_count')
            ->limit(max($limit * 4, 16))
            ->get()
            ->map(function (Animal $animal): array {
                return [
                    'animal' => $animal,
                    'metrics' => $this->damMetrics($animal),
                ];
            })
            ->sortByDesc(fn (array $item): float => (float) $item['metrics']['score'])
            ->take($limit)
            ->values();
    }

    public function latestReview(Animal $animal): ?AnimalBreedingReview
    {
        return AnimalBreedingReview::query()
            ->where('animal_id', $animal->id)
            ->latest('reviewed_at')
            ->latest('id')
            ->first();
    }

    private function buildDescendantNode(
        Animal $animal,
        int $level,
        int $maxLevel,
        array &$visited
    ): array {
        if (isset($visited[$animal->id])) {
            return $this->node($animal, $level, [], true);
        }

        $visited[$animal->id] = true;
        $children = [];

        if ($level < $maxLevel) {
            $children = Animal::query()
                ->with(['breed:id,breed_name', 'location:id,name'])
                ->where(function ($query) use ($animal): void {
                    $query->where('sire_id', $animal->id)
                        ->orWhere('dam_id', $animal->id);
                })
                ->orderBy('date_of_birth')
                ->orderBy('tag_number')
                ->get()
                ->map(fn (Animal $child): array => $this->buildDescendantNode(
                    $child,
                    $level + 1,
                    $maxLevel,
                    $visited
                ))
                ->all();
        }

        return $this->node($animal, $level, $children);
    }

    private function buildAncestorNode(
        Animal $animal,
        int $level,
        int $maxLevel,
        array &$visited
    ): array {
        if (isset($visited[$animal->id])) {
            return $this->node($animal, $level, [], true);
        }

        $visited[$animal->id] = true;
        $children = [];

        if ($level < $maxLevel) {
            $animal->loadMissing([
                'sire.breed:id,breed_name',
                'dam.breed:id,breed_name',
            ]);

            foreach ([$animal->sire, $animal->dam] as $parent) {
                if ($parent) {
                    $children[] = $this->buildAncestorNode(
                        $parent,
                        $level + 1,
                        $maxLevel,
                        $visited
                    );
                }
            }
        }

        return $this->node($animal, $level, $children);
    }

    private function node(Animal $animal, int $level, array $children, bool $circular = false): array
    {
        $animal->loadMissing(['breed:id,breed_name', 'location:id,name']);

        return [
            'id' => $animal->id,
            'tag_number' => $animal->tag_number,
            'sex' => $animal->sex,
            'species' => $animal->species,
            'breed' => $animal->breed?->breed_name,
            'date_of_birth' => $animal->date_of_birth?->format('d M Y'),
            'status' => $animal->status,
            'is_breeder' => (bool) $animal->is_breeder,
            'purity' => $animal->breed_purity_percent !== null
                ? round((float) $animal->breed_purity_percent, 2)
                : null,
            'location' => $animal->location?->name,
            'level' => $level,
            'circular' => $circular,
            'children' => $children,
        ];
    }

    private function flattenTree(array $node): Collection
    {
        $items = collect([$node]);

        foreach ($node['children'] ?? [] as $child) {
            $items = $items->merge($this->flattenTree($child));
        }

        return $items;
    }

    private function averageScore(Collection $records, string $column): float
    {
        $values = $records
            ->pluck($column)
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): float => (float) $value);

        return $values->isNotEmpty() ? (float) $values->avg() : 0;
    }

    private function damRecommendation(
        int $services,
        int $abortions,
        int $notPregnant,
        float $mothering,
        float $score,
        float $conceptionRate
    ): string {
        return match (true) {
            $services === 0 => 'insufficient_data',
            $abortions >= 2 => 'cull',
            $services >= 2 && $mothering > 0 && $mothering <= 2 => 'cull',
            $services >= 3 && $conceptionRate < 45 => 'sell',
            $notPregnant >= 2 && $services >= 3 => 'sell',
            $score < 45 => 'cull',
            $score < 65 => 'sell',
            $score < 78 => 'monitor',
            default => 'retain',
        };
    }

    private function sireReason(int $offspring, float $score, float $survival, float $conversion): string
    {
        if ($offspring === 0) {
            return 'No registered progeny are linked to this sire yet.';
        }

        return sprintf(
            '%d direct offspring, %.1f%% survival, %.1f%% retained as breeders, performance score %.1f/100.',
            $offspring,
            $survival,
            $conversion,
            $score
        );
    }

    private function damReason(
        int $services,
        int $abortions,
        int $notPregnant,
        float $mothering,
        float $score,
        float $conceptionRate
    ): string {
        if ($services === 0) {
            return 'No breeding records are available for this female.';
        }

        return sprintf(
            '%d services, %d abortion(s), %d non-pregnant result(s), %.1f%% conception, mothering %.1f/5, score %.1f/100.',
            $services,
            $abortions,
            $notPregnant,
            $conceptionRate,
            $mothering,
            $score
        );
    }
}
