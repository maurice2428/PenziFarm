<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalTagCorrection;
use App\Models\Breed;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class AnimalTagGeneratorService
{
    private const FARM_PREFIX = 'PENZIF';

    /**
     * Preview the next automatically generated tag.
     *
     * This method does not reserve or consume a sequence.
     */
    public function previewForBreedAndBirthDate(
        Breed $breed,
        mixed $birthDate
    ): array {
        $year = $this->birthYear($birthDate);
        $prefix = $this->tagPrefix($breed, $year);

        $lastNumber = max(
            $this->counterLastNumber($prefix),
            $this->highestEverIssuedSequence($prefix),
        );

        $nextNumber = $lastNumber + 1;
        $tagNumber = $this->formatTag($breed, $year, $nextNumber);

        $this->assertTagAvailable($tagNumber);

        return [
            'tag_number' => $tagNumber,
            'tag_sequence' => $nextNumber,
            'birth_year' => $year,
            'tag_prefix' => $prefix,
        ];
    }

    /**
     * Preview an exact sequence for an authorised correction.
     *
     * Unlike normal generation, the sequence may be lower than the current
     * counter, provided the resulting visible tag is not used by another
     * animal.
     */
    public function previewSpecificTag(
        Breed $breed,
        mixed $birthDate,
        int $sequence,
        ?Animal $exceptAnimal = null,
    ): array {
        if ($sequence < 1) {
            throw ValidationException::withMessages([
                'tag_sequence' => 'The tally number must be at least 1.',
            ]);
        }

        $year = $this->birthYear($birthDate);
        $prefix = $this->tagPrefix($breed, $year);
        $tagNumber = $this->formatTag($breed, $year, $sequence);

        $this->assertTagAvailable(
            $tagNumber,
            $exceptAnimal?->getKey()
        );

        return [
            'tag_number' => $tagNumber,
            'tag_sequence' => $sequence,
            'birth_year' => $year,
            'tag_prefix' => $prefix,
        ];
    }

    /**
     * Generate and permanently reserve the next sequence for a visible tag
     * prefix such as PENZIFD25.
     *
     * The counter is keyed by visible prefix rather than breed ID. This is
     * important because two breeds beginning with the same letter share the
     * same visible Penzi prefix.
     */
    public function generateForBreedAndBirthDate(
        Breed $breed,
        mixed $birthDate
    ): array {
        $year = $this->birthYear($birthDate);
        $prefix = $this->tagPrefix($breed, $year);

        return DB::transaction(function () use (
            $breed,
            $year,
            $prefix
        ): array {
            $counter = $this->counterForUpdate($prefix);

            $lastNumber = max(
                (int) $counter->last_number,
                $this->highestEverIssuedSequence($prefix),
            );

            $nextNumber = $lastNumber + 1;
            $tagNumber = $this->formatTag(
                $breed,
                $year,
                $nextNumber
            );

            $this->assertTagAvailable($tagNumber);

            DB::table('animal_tag_prefix_counters')
                ->where('id', $counter->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return [
                'tag_number' => $tagNumber,
                'tag_sequence' => $nextNumber,
                'birth_year' => $year,
                'tag_prefix' => $prefix,
            ];
        }, 5);
    }

    /**
     * Compatibility method for imports that provide a complete Penzi tag.
     */
    public function reserveProvidedTag(
        Breed $breed,
        mixed $birthDate,
        string $providedTag
    ): array {
        $year = $this->birthYear($birthDate);
        $prefix = $this->tagPrefix($breed, $year);

        $tag = strtoupper(
            preg_replace('/\s+/', '', trim($providedTag))
        );

        $pattern = '/^'
            . preg_quote($prefix, '/')
            . '(\d{2,})$/';

        if (! preg_match($pattern, $tag, $matches)) {
            throw new RuntimeException(
                "Tag {$tag} is invalid. Expected a value like "
                . $this->formatTag($breed, $year, 1)
            );
        }

        $sequence = (int) $matches[1];

        if ($sequence < 1) {
            throw new RuntimeException(
                "Tag {$tag} has an invalid tally number."
            );
        }

        return DB::transaction(function () use (
            $prefix,
            $tag,
            $sequence,
            $year
        ): array {
            $this->assertTagAvailable($tag);
            $this->raiseCounterToAtLeast($prefix, $sequence);

            return [
                'tag_number' => $tag,
                'tag_sequence' => $sequence,
                'birth_year' => $year,
                'tag_prefix' => $prefix,
            ];
        }, 5);
    }

    /**
     * Correct an existing animal identity and retain a complete audit trail.
     *
     * A lower unused sequence is allowed. Counters are only raised and are
     * never reduced, so future automatic tags cannot move backwards.
     */
    public function correctExistingAnimalTag(
        Animal $animal,
        Breed $newBreed,
        mixed $newBirthDate,
        int $newSequence,
        string $reason,
        ?int $correctedBy = null,
    ): Animal {
        if ($newSequence < 1) {
            throw ValidationException::withMessages([
                'tag_sequence' =>
                    'The corrected tally number must be at least 1.',
            ]);
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' =>
                    'Provide a clear correction reason of at least 10 characters.',
            ]);
        }

        $newDate = $this->parseBirthDate($newBirthDate);
        $newYear = (int) $newDate->year;
        $newPrefix = $this->tagPrefix($newBreed, $newYear);
        $newTagNumber = $this->formatTag(
            $newBreed,
            $newYear,
            $newSequence
        );

        return DB::transaction(function () use (
            $animal,
            $newBreed,
            $newDate,
            $newSequence,
            $newPrefix,
            $newTagNumber,
            $reason,
            $correctedBy,
        ): Animal {
            $lockedAnimal = Animal::query()
                ->whereKey($animal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTagAvailable(
                $newTagNumber,
                $lockedAnimal->getKey()
            );

            $oldTagNumber = strtoupper(
                trim((string) $lockedAnimal->tag_number)
            );
            $oldBreedId = (int) $lockedAnimal->breed_id;
            $oldDate = $lockedAnimal->date_of_birth
                ? Carbon::parse($lockedAnimal->date_of_birth)->startOfDay()
                : null;
            $oldSequence = (int) $lockedAnimal->tag_sequence;

            $oldPrefix = null;

            if ($oldDate && filled($oldTagNumber)) {
                $oldPrefix = $this->extractVisiblePrefix(
                    $oldTagNumber,
                    $oldSequence
                );

                if (! $oldPrefix) {
                    $oldBreed = Breed::query()->find($oldBreedId);

                    if ($oldBreed) {
                        $oldPrefix = $this->tagPrefix(
                            $oldBreed,
                            (int) $oldDate->year
                        );
                    }
                }
            }

            $breedChanged =
                $oldBreedId !== (int) $newBreed->getKey();

            $dateChanged =
                optional($oldDate)->toDateString()
                !== $newDate->toDateString();

            $sequenceChanged =
                $oldSequence !== $newSequence;

            $tagChanged =
                $oldTagNumber !== strtoupper($newTagNumber);

            if (
                ! $breedChanged
                && ! $dateChanged
                && ! $sequenceChanged
                && ! $tagChanged
            ) {
                throw ValidationException::withMessages([
                    'tag_sequence' =>
                        'The corrected identity is the same as the current identity.',
                ]);
            }

            $this->validateExistingLineage(
                $lockedAnimal,
                (string) $newBreed->parent_category,
                $newDate
            );

            /*
             * Keep the old issued number consumed, even when the animal is
             * being corrected to a smaller number or another prefix.
             */
            if ($oldPrefix && $oldSequence > 0) {
                $this->raiseCounterToAtLeast(
                    $oldPrefix,
                    $oldSequence
                );
            }

            /*
             * A backward correction does not lower this counter. A correction
             * above the current counter raises it to protect future creation.
             */
            $this->raiseCounterToAtLeast(
                $newPrefix,
                $newSequence
            );

            $correctionType = $this->correctionType(
                $breedChanged,
                $dateChanged,
                $sequenceChanged || $tagChanged
            );

            $oldBreedValue = $lockedAnimal->breed_id;
            $oldDateValue = $lockedAnimal->date_of_birth;

            $lockedAnimal->forceFill([
                'breed_id' => $newBreed->getKey(),
                'species' => $newBreed->parent_category,
                'purity_breed_id' => $newBreed->getKey(),
                'date_of_birth' => $newDate->toDateString(),
                'tag_number' => $newTagNumber,
                'tag_sequence' => $newSequence,
                'updated_by' => $correctedBy ?? auth()->id(),
            ]);

            $lockedAnimal->save();

            AnimalTagCorrection::query()->create([
                'animal_id' => $lockedAnimal->getKey(),
                'old_tag_number' => $oldTagNumber,
                'new_tag_number' => $newTagNumber,
                'old_breed_id' => $oldBreedValue,
                'new_breed_id' => $newBreed->getKey(),
                'old_date_of_birth' => $oldDateValue,
                'new_date_of_birth' => $newDate->toDateString(),
                'correction_type' => $correctionType,
                'reason' => $reason,
                'corrected_by' => $correctedBy ?? auth()->id(),
            ]);

            return $lockedAnimal->refresh();
        }, 5);
    }

    private function counterLastNumber(string $prefix): int
    {
        if (! Schema::hasTable('animal_tag_prefix_counters')) {
            return 0;
        }

        return (int) (
            DB::table('animal_tag_prefix_counters')
                ->where('tag_prefix', $prefix)
                ->value('last_number') ?? 0
        );
    }

    private function counterForUpdate(string $prefix): object
    {
        if (! Schema::hasTable('animal_tag_prefix_counters')) {
            throw new RuntimeException(
                'The animal tag correction migration has not been run. '
                . 'Run php artisan migrate first.'
            );
        }

        $counter = DB::table('animal_tag_prefix_counters')
            ->where('tag_prefix', $prefix)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            return $counter;
        }

        try {
            DB::table('animal_tag_prefix_counters')->insert([
                'tag_prefix' => $prefix,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            $mysqlErrorCode = (int) ($exception->errorInfo[1] ?? 0);
            $sqlState = (string) ($exception->errorInfo[0] ?? '');

            $isDuplicate =
                $mysqlErrorCode === 1062
                || in_array($sqlState, ['23000', '23505'], true);

            if (! $isDuplicate) {
                throw $exception;
            }
        }

        $counter = DB::table('animal_tag_prefix_counters')
            ->where('tag_prefix', $prefix)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            throw new RuntimeException(
                "Unable to initialise the tag counter for {$prefix}."
            );
        }

        return $counter;
    }

    private function raiseCounterToAtLeast(
        string $prefix,
        int $minimumSequence
    ): void {
        $counter = $this->counterForUpdate($prefix);
        $currentNumber = (int) $counter->last_number;

        if ($currentNumber >= $minimumSequence) {
            return;
        }

        DB::table('animal_tag_prefix_counters')
            ->where('id', $counter->id)
            ->update([
                'last_number' => $minimumSequence,
                'updated_at' => now(),
            ]);
    }

    /**
     * Find the highest number currently or historically issued for a visible
     * prefix. Correction history ensures retired numbers are not reused.
     */
    private function highestEverIssuedSequence(string $prefix): int
    {
        $tags = Animal::query()
            ->where('tag_number', 'like', $prefix . '%')
            ->pluck('tag_number');

        if (Schema::hasTable('animal_tag_corrections')) {
            $historicalTags = AnimalTagCorrection::query()
                ->where(function ($query) use ($prefix): void {
                    $query
                        ->where(
                            'old_tag_number',
                            'like',
                            $prefix . '%'
                        )
                        ->orWhere(
                            'new_tag_number',
                            'like',
                            $prefix . '%'
                        );
                })
                ->get([
                    'old_tag_number',
                    'new_tag_number',
                ])
                ->flatMap(
                    fn (AnimalTagCorrection $correction): array => [
                        $correction->old_tag_number,
                        $correction->new_tag_number,
                    ]
                )
                ->filter();

            $tags = $tags->concat($historicalTags);
        }

        $highest = 0;

        foreach ($tags as $tag) {
            if (
                preg_match(
                    '/^'
                    . preg_quote($prefix, '/')
                    . '(\d+)$/',
                    strtoupper((string) $tag),
                    $matches
                )
            ) {
                $highest = max(
                    $highest,
                    (int) $matches[1]
                );
            }
        }

        return $highest;
    }

    private function assertTagAvailable(
        string $tagNumber,
        ?int $exceptAnimalId = null,
    ): void {
        $query = Animal::query()
            ->where('tag_number', strtoupper($tagNumber));

        if ($exceptAnimalId) {
            $query->whereKeyNot($exceptAnimalId);
        }

        $existingAnimal = $query->first([
            'id',
            'breed_id',
            'tag_number',
        ]);

        if (! $existingAnimal) {
            return;
        }

        throw ValidationException::withMessages([
            'tag_sequence' =>
                "The tag {$tagNumber} is already assigned to another animal.",
        ]);
    }

    private function validateExistingLineage(
        Animal $animal,
        string $newSpecies,
        Carbon $newDate
    ): void {
        $parentIds = collect([
            $animal->sire_id,
            $animal->dam_id,
        ])->filter()->map(fn ($id): int => (int) $id)->all();

        if ($parentIds === []) {
            return;
        }

        $parents = Animal::query()
            ->whereIn('id', $parentIds)
            ->get()
            ->keyBy('id');

        $requirements = [
            'sire_id' => [
                'id' => $animal->sire_id,
                'sex' => 'Male',
                'label' => 'sire',
            ],
            'dam_id' => [
                'id' => $animal->dam_id,
                'sex' => 'Female',
                'label' => 'dam',
            ],
        ];

        foreach ($requirements as $field => $requirement) {
            if (blank($requirement['id'])) {
                continue;
            }

            $parent = $parents->get((int) $requirement['id']);

            if (! $parent) {
                throw ValidationException::withMessages([
                    $field =>
                        "The current {$requirement['label']} record no longer exists.",
                ]);
            }

            if ($parent->sex !== $requirement['sex']) {
                throw ValidationException::withMessages([
                    $field =>
                        "The current {$requirement['label']} has an invalid sex.",
                ]);
            }

            if ($parent->species !== $newSpecies) {
                throw ValidationException::withMessages([
                    $field =>
                        "The corrected identity would make the current {$requirement['label']} a different species. Remove or correct the parent first.",
                ]);
            }

            if (
                $parent->date_of_birth
                && Carbon::parse($parent->date_of_birth)
                    ->greaterThan($newDate->copy()->subYear())
            ) {
                throw ValidationException::withMessages([
                    $field =>
                        "The corrected date would make the current {$requirement['label']} less than one year older than the animal.",
                ]);
            }
        }
    }

    private function correctionType(
        bool $breedChanged,
        bool $dateChanged,
        bool $tagChanged
    ): string {
        $changes = [];

        if ($breedChanged) {
            $changes[] = 'breed';
        }

        if ($dateChanged) {
            $changes[] = 'date_of_birth';
        }

        if ($tagChanged) {
            $changes[] = 'tag_sequence';
        }

        return implode('_and_', $changes) ?: 'tag_sequence';
    }

    private function extractVisiblePrefix(
        string $tagNumber,
        int $sequence
    ): ?string {
        if ($sequence < 1) {
            return null;
        }

        $plainSequence = (string) $sequence;
        $paddedSequence = str_pad(
            $plainSequence,
            2,
            '0',
            STR_PAD_LEFT
        );

        foreach ([$paddedSequence, $plainSequence] as $suffix) {
            if (str_ends_with($tagNumber, $suffix)) {
                return substr(
                    $tagNumber,
                    0,
                    -strlen($suffix)
                );
            }
        }

        return null;
    }

    private function birthYear(mixed $birthDate): int
    {
        return (int) $this->parseBirthDate($birthDate)->year;
    }

    private function parseBirthDate(mixed $birthDate): Carbon
    {
        if (blank($birthDate)) {
            throw ValidationException::withMessages([
                'date_of_birth' =>
                    'Date of birth is required to generate a Penzi tag.',
            ]);
        }

        try {
            $date = Carbon::parse($birthDate)->startOfDay();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Date of birth is invalid.',
            ]);
        }

        if ($date->isFuture()) {
            throw ValidationException::withMessages([
                'date_of_birth' =>
                    'Date of birth cannot be in the future.',
            ]);
        }

        return $date;
    }

    /**
     * Use the first alphabetic character of the actual breed name.
     */
    private function breedLetter(Breed $breed): string
    {
        $breedName = strtoupper(
            trim((string) $breed->breed_name)
        );

        if (! preg_match('/[A-Z]/', $breedName, $matches)) {
            throw new RuntimeException(
                'A valid breed name is required to generate a tag.'
            );
        }

        return $matches[0];
    }

    private function tagPrefix(Breed $breed, int $year): string
    {
        return self::FARM_PREFIX
            . $this->breedLetter($breed)
            . substr((string) $year, -2);
    }

    private function formatTag(
        Breed $breed,
        int $year,
        int $sequence
    ): string {
        return $this->tagPrefix($breed, $year)
            . str_pad(
                (string) $sequence,
                2,
                '0',
                STR_PAD_LEFT
            );
    }
}
