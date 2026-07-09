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

        $targetBreedId = (int) (
            $animal->purity_breed_id
                ?: $animal->breed_id
        );

        $updates = [
            'purity_breed_id' => $targetBreedId,
        ];

        /*
         * Foundation stock is deliberately approved as 100%.
         */
        if ($animal->is_foundation_animal) {
            $updates['breed_purity_percent'] = 100.0;
            $updates['purity_status'] = 'foundation';
        }
        /*
         * DNA-tested or manually verified result.
         * The override remains protected from automatic recalculation.
         */ elseif ($animal->purity_override_percent !== null) {
            $updates['breed_purity_percent'] = $this->normalise(
                (float) $animal->purity_override_percent
            );

            $updates['purity_status'] = in_array(
                $animal->purity_status,
                ['dna_verified', 'manual_verified'],
                true
            )
                ? $animal->purity_status
                : 'manual_verified';
        }
        /*
         * Normal pedigree calculation:
         * offspring purity = (sire contribution + dam contribution) / 2
         */ else {
            $sireContribution = $this->parentContribution(
                $animal->sire,
                $targetBreedId
            );

            $damContribution = $this->parentContribution(
                $animal->dam,
                $targetBreedId
            );

            if (
                $sireContribution !== null &&
                $damContribution !== null
            ) {
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

        if (!$cascadeToDescendants) {
            return $animal;
        }

        $descendants = $animal
            ->offspringAsSire
            ->merge($animal->offspringAsDam)
            ->unique('id');

        foreach ($descendants as $descendant) {
            $this->recalculate(
                $descendant,
                true,
                $visitedAnimalIds
            );
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
        if (!$targetBreedId) {
            return [
                'status' => 'pending',
                'percent' => null,
                'label' => 'Select the target foundation breed.',
            ];
        }

        if ($isFoundationAnimal) {
            return [
                'status' => 'foundation',
                'percent' => 100.0,
                'label' => 'Foundation Stock · 100.00%',
            ];
        }

        if ($overridePercent !== null) {
            return [
                'status' => 'manual_verified',
                'percent' => $this->normalise($overridePercent),
                'label' => 'Verified Purity · '
                    . number_format($this->normalise($overridePercent), 2)
                    . '%',
            ];
        }

        $sire = $sireId
            ? Animal::query()->find($sireId)
            : null;

        $dam = $damId
            ? Animal::query()->find($damId)
            : null;

        $sireContribution = $this->parentContribution(
            $sire,
            $targetBreedId
        );

        $damContribution = $this->parentContribution(
            $dam,
            $targetBreedId
        );

        if (
            $sireContribution === null ||
            $damContribution === null
        ) {
            return [
                'status' => 'pending',
                'percent' => null,
                'label' => 'Pending Parentage / Purity Data',
            ];
        }

        $percent = $this->normalise(
            ($sireContribution + $damContribution) / 2
        );

        return [
            'status' => 'calculated',
            'percent' => $percent,
            'label' => 'Calculated Purity · '
                . number_format($percent, 2)
                . '%',
        ];
    }

    private function parentContribution(
        ?Animal $parent,
        int $targetBreedId,
    ): ?float {
        if (!$parent) {
            return null;
        }

        $parentTargetBreedId = (int) (
            $parent->purity_breed_id
                ?: $parent->breed_id
        );

        /*
         * Parent belongs to another recognised breed:
         * it contributes zero percent to the selected target breed.
         */
        if ($parentTargetBreedId !== $targetBreedId) {
            return 0.0;
        }

        if ($parent->is_foundation_animal) {
            return 100.0;
        }

        if ($parent->purity_override_percent !== null) {
            return $this->normalise(
                (float) $parent->purity_override_percent
            );
        }

        if ($parent->breed_purity_percent !== null) {
            return $this->normalise(
                (float) $parent->breed_purity_percent
            );
        }

        return null;
    }

    private function normalise(float $percentage): float
    {
        return round(
            max(0, min(100, $percentage)),
            4
        );
    }
}
