<?php

namespace App\Services\Breeding;

use App\Models\Animal;
use App\Models\BreedingBatch;
use App\Models\BreedingGestationRule;
use App\Models\BreedingRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BreedingBatchService
{
    public function validateBatchSelections(array $data, array $femaleIds): void
    {
        $male = Animal::query()
            ->with('breed')
            ->find($data['male_animal_id'] ?? null);

        if (! $male) {
            throw ValidationException::withMessages([
                'data.male_animal_id' => 'Please select a valid male animal.',
            ]);
        }

        if ($male->sex !== 'Male') {
            throw ValidationException::withMessages([
                'data.male_animal_id' => 'The selected sire must be male.',
            ]);
        }

        if (empty($femaleIds)) {
            throw ValidationException::withMessages([
                'data.female_animal_ids' => 'Please select at least one female animal.',
            ]);
        }

        $breedingType = $data['breeding_type'] ?? 'natural';
        $allowCrossBreeding = (bool) ($data['allow_cross_breeding'] ?? false);

        $errors = [];

        foreach ($femaleIds as $femaleId) {
            $female = Animal::query()
                ->with('breed')
                ->find($femaleId);

            if (! $female) {
                $errors[] = "Female ID {$femaleId} was not found.";
                continue;
            }

            if ($female->sex !== 'Female') {
                $errors[] = "{$female->tag_number} is not female.";
                continue;
            }

            if (($female->status ?? null) !== 'Active' || (bool) ($female->is_archived ?? false)) {
                $errors[] = "{$female->tag_number} is not an active animal.";
                continue;
            }

            $maleSpecies = $this->animalSpecies($male);
            $femaleSpecies = $this->animalSpecies($female);

            if ($maleSpecies && $femaleSpecies && $maleSpecies !== $femaleSpecies) {
                $errors[] = "{$female->tag_number} cannot be bred with {$male->tag_number}. Cross-species breeding is not allowed.";
                continue;
            }

            if (! $allowCrossBreeding && (int) $male->breed_id !== (int) $female->breed_id) {
                $errors[] = "{$female->tag_number} is a different breed. Enable cross breeding to allow this.";
                continue;
            }

            $activeBreeding = BreedingRecord::query()
                ->where('female_animal_id', $female->id)
                ->whereIn('pregnancy_status', ['pending', 'confirmed'])
                ->exists();

            if ($activeBreeding) {
                $errors[] = "{$female->tag_number} already has an active breeding/pregnancy record.";
                continue;
            }

            $relationship = $this->checkRelationship($male, $female);

            if ($breedingType === 'natural' && $relationship['blocked']) {
                $errors[] = "{$female->tag_number} cannot be recorded under natural breeding with {$male->tag_number}: {$relationship['summary']}";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'data.female_animal_ids' => implode("\n", $errors),
            ]);
        }
    }

    public function createRecordsForBatch(BreedingBatch $batch, array $femaleIds): void
    {
        $batch->load('male');

        $male = Animal::query()
            ->with('breed')
            ->findOrFail($batch->male_animal_id);

        $dueDates = [];

        foreach ($femaleIds as $femaleId) {
            $female = Animal::query()
                ->with('breed')
                ->findOrFail($femaleId);

            $gestationDays = $this->gestationDaysFor($female);
            $expectedDueDate = Carbon::parse($batch->mating_date)->addDays($gestationDays);

            $relationship = $this->checkRelationship($male, $female);

            $isCrossBreed = (int) $male->breed_id !== (int) $female->breed_id;

            BreedingRecord::query()->create([
                'breeding_batch_id' => $batch->id,
                'female_animal_id' => $female->id,
                'male_animal_id' => $male->id,
                'female_breed_id' => $female->breed_id,
                'male_breed_id' => $male->breed_id,
                'species' => $this->animalSpecies($female),
                'breeding_type' => $batch->breeding_type,
                'is_cross_breed' => $isCrossBreed,
                'mating_date' => $batch->mating_date,
                'gestation_days' => $gestationDays,
                'expected_due_date' => $expectedDueDate->toDateString(),
                'inbreeding_status' => $relationship['blocked'] ? 'blocked' : ($relationship['warning'] ? 'warning' : 'clear'),
                'relationship_notes' => $relationship['summary'],
                'pregnancy_status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            $dueDates[] = $expectedDueDate->toDateString();
        }

        $batch->forceFill([
            'expected_due_from' => collect($dueDates)->min(),
            'expected_due_to' => collect($dueDates)->max(),
            'total_females' => count($femaleIds),
        ])->saveQuietly();
    }

    public function gestationDaysFor(Animal $female): int
    {
        $species = $this->animalSpecies($female);

        $breedRule = BreedingGestationRule::query()
            ->where('is_active', true)
            ->where('breed_id', $female->breed_id)
            ->first();

        if ($breedRule) {
            return (int) $breedRule->gestation_days;
        }

        $speciesRule = BreedingGestationRule::query()
            ->where('is_active', true)
            ->whereNull('breed_id')
            ->where('species', $species)
            ->first();

        if ($speciesRule) {
            return (int) $speciesRule->gestation_days;
        }

        return match ($species) {
            'Cattle' => 283,
            'Goat' => 150,
            'Sheep' => 147,
            default => 150,
        };
    }

    public function checkRelationship(Animal $male, Animal $female): array
    {
        $notes = [];

        if ((int) $male->id === (int) $female->id) {
            return [
                'blocked' => true,
                'warning' => false,
                'summary' => 'Same animal selected as male and female.',
            ];
        }

        if ((int) $female->sire_id === (int) $male->id) {
            $notes[] = 'Male is the female’s sire.';
        }

        if ((int) $male->sire_id === (int) $female->id || (int) $male->dam_id === (int) $female->id) {
            $notes[] = 'Female is an ancestor/parent of the male.';
        }

        if ($female->sire_id && $male->sire_id && (int) $female->sire_id === (int) $male->sire_id) {
            $notes[] = 'Male and female share the same sire.';
        }

        if ($female->dam_id && $male->dam_id && (int) $female->dam_id === (int) $male->dam_id) {
            $notes[] = 'Male and female share the same dam.';
        }

        $maleAncestors = $this->ancestorIds($male);
        $femaleAncestors = $this->ancestorIds($female);

        if (in_array((int) $male->id, $femaleAncestors, true)) {
            $notes[] = 'Male appears in the female ancestry line.';
        }

        if (in_array((int) $female->id, $maleAncestors, true)) {
            $notes[] = 'Female appears in the male ancestry line.';
        }

        $sharedAncestors = array_intersect($maleAncestors, $femaleAncestors);

        if (! empty($sharedAncestors)) {
            $notes[] = 'Male and female share ancestor ID(s): ' . implode(', ', $sharedAncestors) . '.';
        }

        $blocked = ! empty($notes);

        return [
            'blocked' => $blocked,
            'warning' => $blocked,
            'summary' => $blocked ? implode(' ', $notes) : 'No close relationship detected.',
        ];
    }

    private function ancestorIds(Animal $animal, int $depth = 4, array &$visited = []): array
    {
        if ($depth <= 0) {
            return [];
        }

        $ids = [];

        foreach (['sire_id', 'dam_id'] as $field) {
            $parentId = $animal->{$field};

            if (! $parentId) {
                continue;
            }

            $parentId = (int) $parentId;

            if (isset($visited[$parentId])) {
                continue;
            }

            $visited[$parentId] = true;
            $ids[] = $parentId;

            $parent = Animal::query()->find($parentId);

            if ($parent) {
                $ids = array_merge($ids, $this->ancestorIds($parent, $depth - 1, $visited));
            }
        }

        return array_values(array_unique($ids));
    }

    public function animalSpecies(Animal $animal): ?string
    {
        return $animal->species
            ?? $animal->breed?->parent_category
            ?? null;
    }
}
