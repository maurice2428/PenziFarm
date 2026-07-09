<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Breed;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AnimalTagGeneratorService
{
    private const FARM_PREFIX = 'PENZIF';

    /**
     * Preview only. No sequence is reserved or consumed here.
     */
    public function previewForBreedAndBirthDate(
        Breed $breed,
        mixed $birthDate
    ): array {
        $year = $this->birthYear($birthDate);

        $lastNumber = max(
            $this->counterLastNumber($breed->id, $year),
            $this->highestExistingSequence($breed, $year),
        );

        $nextNumber = $lastNumber + 1;
        $tagNumber = $this->formatTag($breed, $year, $nextNumber);

        $this->assertTagAvailableForBreed($breed, $tagNumber);

        return [
            'tag_number' => $tagNumber,
            'tag_sequence' => $nextNumber,
            'birth_year' => $year,
        ];
    }

    /**
     * Generates and permanently reserves the next yearly sequence.
     *
     * Once a number is issued, the counter is never reduced. Therefore, when
     * PENZIFD2501 is corrected or retired, the next generated tag remains
     * PENZIFD2502 rather than reusing 01.
     */
    public function generateForBreedAndBirthDate(
        Breed $breed,
        mixed $birthDate
    ): array {
        $year = $this->birthYear($birthDate);

        return DB::transaction(function () use ($breed, $year): array {
            $counter = $this->counterForUpdate($breed->id, $year);

            $lastNumber = max(
                (int) $counter->last_number,
                $this->highestExistingSequence($breed, $year),
            );

            $nextNumber = $lastNumber + 1;
            $tagNumber = $this->formatTag($breed, $year, $nextNumber);

            $this->assertTagAvailableForBreed($breed, $tagNumber);

            DB::table('animal_tag_counters')
                ->where('id', $counter->id)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return [
                'tag_number' => $tagNumber,
                'tag_sequence' => $nextNumber,
                'birth_year' => $year,
            ];
        }, 5);
    }

    /**
     * Retained for compatibility with an older CSV importer.
     * New imports should normally leave tag_number blank and use generation.
     */
    public function reserveProvidedTag(
        Breed $breed,
        mixed $birthDate,
        string $providedTag
    ): array {
        $year = $this->birthYear($birthDate);

        $tag = strtoupper(
            preg_replace('/\s+/', '', trim($providedTag))
        );

        $expectedPrefix = $this->tagPrefix($breed, $year);
        $pattern = '/^' . preg_quote($expectedPrefix, '/') . '(\d{2,})$/';

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
            $breed,
            $year,
            $tag,
            $sequence
        ): array {
            $this->assertTagAvailableForBreed($breed, $tag);

            $counter = $this->counterForUpdate($breed->id, $year);

            $lastNumber = max(
                (int) $counter->last_number,
                $this->highestExistingSequence($breed, $year),
                $sequence,
            );

            DB::table('animal_tag_counters')
                ->where('id', $counter->id)
                ->update([
                    'last_number' => $lastNumber,
                    'updated_at' => now(),
                ]);

            return [
                'tag_number' => $tag,
                'tag_sequence' => $sequence,
                'birth_year' => $year,
            ];
        }, 5);
    }

    private function counterLastNumber(int $breedId, int $year): int
    {
        return (int) (
            DB::table('animal_tag_counters')
                ->where('breed_id', $breedId)
                ->where('birth_year', $year)
                ->value('last_number') ?? 0
        );
    }

    private function counterForUpdate(int $breedId, int $year): object
    {
        $counter = DB::table('animal_tag_counters')
            ->where('breed_id', $breedId)
            ->where('birth_year', $year)
            ->lockForUpdate()
            ->first();

        if ($counter) {
            return $counter;
        }

        try {
            DB::table('animal_tag_counters')->insert([
                'breed_id' => $breedId,
                'birth_year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            /*
             * Another request may create this same breed/year counter at the
             * same time. A duplicate-key error is safe; retrieve it below.
             */
            $mysqlErrorCode = (int) ($exception->errorInfo[1] ?? 0);

            if ($mysqlErrorCode !== 1062) {
                throw $exception;
            }
        }

        $counter = DB::table('animal_tag_counters')
            ->where('breed_id', $breedId)
            ->where('birth_year', $year)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            throw new RuntimeException(
                'Unable to initialise the yearly Penzi tag counter.'
            );
        }

        return $counter;
    }

    /**
     * Finds the highest existing tally using this exact tag prefix and year.
     *
     * The query intentionally does not filter by breed_id. Two breeds beginning
     * with the same letter share the same visible prefix, so their issued tags
     * must not collide.
     */
    private function highestExistingSequence(Breed $breed, int $year): int
    {
        $prefix = $this->tagPrefix($breed, $year);

        $tags = Animal::query()
            ->where('tag_number', 'like', $prefix . '%')
            ->pluck('tag_number');

        $highest = 0;

        foreach ($tags as $tag) {
            if (
                preg_match(
                    '/^' . preg_quote($prefix, '/') . '(\d+)$/',
                    strtoupper((string) $tag),
                    $matches
                )
            ) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return $highest;
    }

    private function assertTagAvailableForBreed(
        Breed $breed,
        string $tagNumber
    ): void {
        $existingAnimal = Animal::query()
            ->where('tag_number', $tagNumber)
            ->first(['id', 'breed_id']);

        if (! $existingAnimal) {
            return;
        }

        if ((int) $existingAnimal->breed_id === (int) $breed->id) {
            throw new RuntimeException(
                "The tag {$tagNumber} already exists for {$breed->breed_name}."
            );
        }

        throw new RuntimeException(
            "The tag {$tagNumber} already belongs to another breed. "
            . 'Breeds sharing the same first letter also share the same visible '
            . 'tag prefix and must continue with the next available tally.'
        );
    }

    private function birthYear(mixed $birthDate): int
    {
        if (blank($birthDate)) {
            throw new RuntimeException(
                'Date of birth is required to generate a Penzi tag.'
            );
        }

        try {
            $date = Carbon::parse($birthDate);
        } catch (Throwable) {
            throw new RuntimeException('Date of birth is invalid.');
        }

        if ($date->isFuture()) {
            throw new RuntimeException(
                'Date of birth cannot be in the future.'
            );
        }

        return (int) $date->year;
    }

    /**
     * Uses the first alphabetic character of the actual breed name.
     */
    private function breedLetter(Breed $breed): string
    {
        $breedName = strtoupper(trim((string) $breed->breed_name));

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
            . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }
}
