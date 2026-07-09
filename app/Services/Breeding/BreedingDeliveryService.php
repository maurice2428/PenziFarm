<?php

namespace App\Services\Breeding;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\BreedingBatch;
use App\Models\BreedingRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BreedingDeliveryService
{
    public function recordPregnancyCheck(BreedingRecord $record, array $data): BreedingRecord
    {
        return DB::transaction(function () use ($record, $data): BreedingRecord {
            $record->loadMissing(['batch', 'female', 'male']);

            $status = $data['pregnancy_status'] ?? null;

            if (! in_array($status, ['confirmed', 'not_pregnant', 'aborted'], true)) {
                throw ValidationException::withMessages([
                    'pregnancy_status' => 'Please select a valid pregnancy status.',
                ]);
            }

            $checkedAt = $data['pregnancy_checked_at'] ?? now('Africa/Nairobi')->toDateString();

            $notes = trim((string) ($data['notes'] ?? ''));

            $record->forceFill([
                'pregnancy_status' => $status,
                'pregnancy_checked_at' => $checkedAt,
                'notes' => $this->appendNote(
                    $record->notes,
                    'Pregnancy check: ' . str($status)->replace('_', ' ')->title() . ($notes ? ' - ' . $notes : '')
                ),
            ])->save();

            $record->batch?->forceFill([
                'status' => 'pregnancy_check',
            ])->saveQuietly();

            if ($record->female) {
                $this->createAnimalEvent(
                    animal: $record->female,
                    type: 'pregnancy_check',
                    eventDate: $checkedAt,
                    notes: $notes ?: 'Pregnancy check recorded.',
                    metadata: [
                        'pregnancy_status' => $status,
                        'male_animal_id' => $record->male_animal_id,
                        'female_animal_id' => $record->female_animal_id,
                        'breeding_batch_id' => $record->breeding_batch_id,
                        'breeding_record_id' => $record->id,
                    ],
                    record: $record,
                );
            }

            return $record->refresh();
        });
    }

    public function recordDelivery(BreedingRecord $record, array $data): array
    {
        return DB::transaction(function () use ($record, $data): array {
            $record->loadMissing([
                'batch',
                'female.breed',
                'male.breed',
                'femaleBreed',
                'maleBreed',
            ]);

            if ($record->pregnancy_status === 'delivered') {
                throw ValidationException::withMessages([
                    'delivery_date' => 'This breeding record has already been delivered.',
                ]);
            }

            $deliveryDate = $data['delivery_date'] ?? now('Africa/Nairobi')->toDateString();
            $deliveryNotes = trim((string) ($data['delivery_notes'] ?? ''));

            $offspringRows = collect($data['offspring'] ?? [])
                ->filter(fn ($row) => filled($row['sex'] ?? null))
                ->values();

            if ($offspringRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'offspring' => 'Please add at least one offspring.',
                ]);
            }

            $createdAnimals = [];

            foreach ($offspringRows as $index => $offspringData) {
                $createdAnimals[] = $this->createOffspringAnimal(
                    record: $record,
                    offspringData: $offspringData,
                    deliveryDate: $deliveryDate,
                    sequence: $index + 1,
                );
            }

            $record->forceFill([
                'pregnancy_status' => 'delivered',
                'delivery_date' => $deliveryDate,
                'offspring_count' => count($createdAnimals),
                'delivery_notes' => $deliveryNotes,
                'notes' => $this->appendNote(
                    $record->notes,
                    'Delivery recorded on ' . Carbon::parse($deliveryDate)->format('d M Y') . '. Offspring: ' . count($createdAnimals)
                ),
            ])->save();

            if ($record->female) {
                $this->createAnimalEvent(
                    animal: $record->female,
                    type: 'birth',
                    eventDate: $deliveryDate,
                    notes: $deliveryNotes ?: 'Delivery recorded.',
                    metadata: [
                        'offspring_count' => count($createdAnimals),
                        'offspring_ids' => collect($createdAnimals)->pluck('id')->values()->all(),
                        'offspring_tags' => collect($createdAnimals)->pluck('tag_number')->values()->all(),
                        'male_animal_id' => $record->male_animal_id,
                        'female_animal_id' => $record->female_animal_id,
                        'breeding_batch_id' => $record->breeding_batch_id,
                        'breeding_record_id' => $record->id,
                    ],
                    record: $record,
                );
            }

            foreach ($createdAnimals as $offspring) {
                $this->createAnimalEvent(
                    animal: $offspring,
                    type: 'birth',
                    eventDate: $deliveryDate,
                    notes: 'Created from breeding record ' . $record->id,
                    metadata: [
                        'sire_id' => $record->male_animal_id,
                        'dam_id' => $record->female_animal_id,
                        'breeding_batch_id' => $record->breeding_batch_id,
                        'breeding_record_id' => $record->id,
                    ],
                    record: $record,
                );
            }

            $this->syncBatchStatus($record->batch);

            return $createdAnimals;
        });
    }

    private function createOffspringAnimal(
        BreedingRecord $record,
        array $offspringData,
        string $deliveryDate,
        int $sequence,
    ): Animal {
        $record->loadMissing(['female.breed', 'male.breed']);

        $sex = $offspringData['sex'] ?? null;

        if (! in_array($sex, ['Male', 'Female'], true)) {
            throw ValidationException::withMessages([
                'offspring' => 'Each offspring must have a valid sex.',
            ]);
        }

        $breedId = (int) ($offspringData['breed_id'] ?? $record->female_breed_id ?? $record->female?->breed_id);

        $species = $record->species
            ?: $record->female?->species
            ?: $record->female?->breed?->parent_category;

        $providedTag = trim((string) ($offspringData['tag_number'] ?? ''));

        if ($providedTag !== '') {
            $exists = Animal::query()
                ->where('tag_number', $providedTag)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'offspring' => "Tag {$providedTag} already exists.",
                ]);
            }

            $tagData = [
                'tag_number' => $providedTag,
                'tag_sequence' => null,
            ];
        } else {
            $tagData = $this->generateOffspringTag($breedId);
        }

        $payload = [
            'tag_number' => $tagData['tag_number'],
            'tag_sequence' => $tagData['tag_sequence'],
            'species' => $species,
            'breed_id' => $breedId,
            'sex' => $sex,
            'date_of_birth' => $deliveryDate,
            'date_of_birth_is_estimated' => false,
            'source' => 'Born on farm',
            'sire_id' => $record->male_animal_id,
            'dam_id' => $record->female_animal_id,
            'status' => 'Active',
            'purpose' => $offspringData['purpose'] ?? 'Breeding',
            'is_archived' => false,
            'is_breeder' => false,
            'sale_ready' => false,
            'current_location_id' => $record->female?->current_location_id,
            'created_by' => auth()->id(),
            'notes' => trim((string) ($offspringData['notes'] ?? '')),
        ];

        $payload = $this->filterAnimalPayload($payload);

        $animal = new Animal();
        $animal->forceFill($payload);
        $animal->save();

        return $animal;
    }

    private function generateOffspringTag(?int $breedId): array
    {
        $breed = $breedId ? Breed::query()->find($breedId) : null;

        $prefix = $breed?->prefix;

        if (blank($prefix)) {
            $prefix = $breed?->breed_name
                ? Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $breed->breed_name), 0, 3))
                : 'BRD';
        }

        $prefix = Str::upper(trim($prefix));
        $hasTagSequence = Schema::hasColumn('animals', 'tag_sequence');

        $sequence = 1;

        if ($hasTagSequence && $breedId) {
            $sequence = (int) Animal::query()
                ->where('breed_id', $breedId)
                ->max('tag_sequence') + 1;
        } else {
            $sequence = (int) Animal::query()
                ->where('breed_id', $breedId)
                ->count() + 1;
        }

        do {
            $tagNumber = $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $exists = Animal::query()->where('tag_number', $tagNumber)->exists();

            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return [
            'tag_number' => $tagNumber,
            'tag_sequence' => $sequence,
        ];
    }

    private function filterAnimalPayload(array $payload): array
    {
        return collect($payload)
            ->filter(function ($value, string $column): bool {
                return Schema::hasColumn('animals', $column);
            })
            ->toArray();
    }

    private function createAnimalEvent(
        Animal $animal,
        string $type,
        string $eventDate,
        ?string $notes,
        array $metadata,
        ?BreedingRecord $record = null,
    ): void {
        if (! Schema::hasTable('animal_events')) {
            return;
        }

        $payload = [
            'animal_id' => $animal->id,
            'type' => $type,
            'event_date' => $eventDate,
            'breeding_batch_id' => $record?->breeding_batch_id,
            'breeding_record_id' => $record?->id,
            'performed_by' => auth()->id(),
            'notes' => $notes,
            'metadata' => json_encode($metadata),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = collect($payload)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn('animal_events', $column))
            ->toArray();

        DB::table('animal_events')->insert($payload);
    }

    private function syncBatchStatus(?BreedingBatch $batch): void
    {
        if (! $batch) {
            return;
        }

        $totalRecords = $batch->records()->count();

        if ($totalRecords === 0) {
            return;
        }

        $deliveredRecords = $batch->records()
            ->where('pregnancy_status', 'delivered')
            ->count();

        if ($deliveredRecords >= $totalRecords) {
            $batch->forceFill([
                'status' => 'delivered',
            ])->saveQuietly();

            return;
        }

        $checkedRecords = $batch->records()
            ->whereNotNull('pregnancy_checked_at')
            ->count();

        if ($checkedRecords > 0) {
            $batch->forceFill([
                'status' => 'pregnancy_check',
            ])->saveQuietly();
        }
    }

    private function appendNote(?string $existing, string $newNote): string
    {
        $newNote = trim($newNote);

        if (blank($existing)) {
            return '[' . now('Africa/Nairobi')->format('d M Y H:i') . '] ' . $newNote;
        }

        return trim($existing) . "\n" . '[' . now('Africa/Nairobi')->format('d M Y H:i') . '] ' . $newNote;
    }
}
