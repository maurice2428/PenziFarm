<?php

namespace App\Services;

use App\Models\Animal;

class BreedPurityService
{
    public function recalculate(
        Animal $animal,
        bool $cascadeToDescendants = true,
        array $visitedAnimalIds = [],
    ): Animal {
        if (in_array($animal->id, $visitedAnimalIds, true)) {
            return $animal;
        }

        $visitedAnimalIds[] = $animal->id;

        $animal->loadMissing([
            'sire',
            'dam',
            'offspringAsSire',
            'offspringAsDam',
        ]);

        $targetBreedId = (int) ($animal->breed_id ?: 0);

        $updates = [
            'purity_breed_id' => $targetBreedId ?: null,
        ];

        if (! $targetBreedId) {
            $updates['breed_purity_percent'] = null;
            $updates['purity_status'] = 'pending';
        } elseif ($animal->is_foundation_animal) {
            $updates['breed_purity_percent'] = 100.0000;
            $updates['purity_status'] = 'foundation';
        } elseif ($animal->purity_override_percent !== null) {
            $updates['breed_purity_percent'] = $this->normalise(
                (float) $animal->purity_override_percent
            );
            $updates['purity_status'] = in_array(
                $animal->purity_status,
                ['dna_verified', 'manual_verified'],
                true
            ) ? $animal->purity_status : 'manual_verified';
        } else {
            $sireContribution = $this->parentContribution($animal->sire, $targetBreedId);
            $damContribution = $this->parentContribution($animal->dam, $targetBreedId);

            if ($sireContribution !== null && $damContribution !== null) {
                $updates['breed_purity_percent'] = $this->normalise(
                    ($sireContribution + $damContribution) / 2
                );
                $updates['purity_status'] = 'calculated';
            } else {
                $updates['breed_purity_percent'] = null;
                $updates['purity_status'] = 'pending';
            }
        }

        $animal->forceFill($updates);

        if ($animal->isDirty()) {
            $animal->saveQuietly();
        }

        if ($cascadeToDescendants) {
            $descendants = $animal->offspringAsSire
                ->merge($animal->offspringAsDam)
                ->unique('id');

            foreach ($descendants as $descendant) {
                $this->recalculate($descendant, true, $visitedAnimalIds);
            }
        }

        return $animal->fresh();
    }

    public function preview(
        ?int $targetBreedId,
        ?int $sireId,
        ?int $damId,
        bool $isFoundationAnimal = false,
        ?float $overridePercent = null,
    ): array {
        if (! $targetBreedId) {
            return [
                'status' => 'pending',
                'percent' => null,
                'label' => 'Select a breed under Animal Identity.',
            ];
        }

        if ($isFoundationAnimal) {
            return [
                'status' => 'foundation',
                'percent' => 100.0000,
                'label' => 'Foundation Stock · 100.00%',
            ];
        }

        if ($overridePercent !== null) {
            $percent = $this->normalise($overridePercent);

            return [
                'status' => 'manual_verified',
                'percent' => $percent,
                'label' => 'Verified Purity · ' . number_format($percent, 2) . '%',
            ];
        }

        $sire = $sireId ? Animal::query()->find($sireId) : null;
        $dam = $damId ? Animal::query()->find($damId) : null;
        $sireContribution = $this->parentContribution($sire, $targetBreedId);
        $damContribution = $this->parentContribution($dam, $targetBreedId);

        if ($sireContribution === null || $damContribution === null) {
            return [
                'status' => 'pending',
                'percent' => null,
                'label' => 'Pending Parentage / Purity Data',
            ];
        }

        $percent = $this->normalise(($sireContribution + $damContribution) / 2);

        return [
            'status' => 'calculated',
            'percent' => $percent,
            'label' => 'Calculated Purity · ' . number_format($percent, 2) . '%',
        ];
    }

    private function parentContribution(?Animal $parent, int $targetBreedId): ?float
    {
        if (! $parent) {
            return null;
        }

        if ((int) $parent->breed_id !== $targetBreedId) {
            return 0.0000;
        }

        if ($parent->is_foundation_animal) {
            return 100.0000;
        }

        if ($parent->purity_override_percent !== null) {
            return $this->normalise((float) $parent->purity_override_percent);
        }

        if ($parent->breed_purity_percent !== null) {
            return $this->normalise((float) $parent->breed_purity_percent);
        }

        return null;
    }

    private function normalise(float $percentage): float
    {
        return round(max(0, min(100, $percentage)), 4);
    }
}
