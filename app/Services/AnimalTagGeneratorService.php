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
     * Preview only. No tag number is reserved here.
     */
    public function previewForBreedAndBirthDate(Breed $breed, mixed $birthDate): array
    {
        $year = $this->birthYear($birthDate);

        $lastNumber = max(
            $this->counterLastNumber($breed->id, $year),
            $this->highestExistingSequence($breed, $year),
        );

        $nextNumber = $lastNumber + 1;

        $this->assertTagAvailableForBreed(
            $breed,
            $this->formatTag($breed, $year, $nextNumber)
        );

        return [
            'tag_number' => $this->formatTag($breed, $year, $nextNumber),
            'tag_sequence' => $nextNumber,
            'birth_year' => $year,
        ];
    }

    /**
     * Generates the next tag safely and reserves its yearly tally.
     */
    public function generateForBreedAndBirthDate(Breed $breed, mixed $birthDate): array
    {
        $year = $this->birthYear($birthDate);

        return DB::transaction(function () use ($breed, $year) {
            $counter = $this->counterForUpdate($breed->id, $year);

            /*
             * Uses whichever is higher:
             * 1. Stored yearly counter
             * 2. Existing Penzi-tagged animals already in the database
             */
            $lastNumber = max(
                (int) $counter->last_number,
                $this->highestExistingSequence($breed, $year),
            );

            $nextNumber = $lastNumber + 1;

            $tagNumber = $this->formatTag(
                $breed,
                $year,
                $nextNumber
            );

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
     * The new Excel import should leave tag_number blank.
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

        if (!preg_match($pattern, $tag, $matches)) {
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
        ) {
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
             * Another request may create the same breed/year counter
             * at the same moment. Retrieve that counter below.
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

        if (!$counter) {
            throw new RuntimeException(
                'Unable to initialise the yearly Penzi tag counter.'
            );
        }

        return $counter;
    }

    /**
     * Finds the highest existing Penzi tally for this exact breed/year.
     * Existing records remain untouched.
     */
    private function highestExistingSequence(Breed $breed, int $year): int
    {
        $prefix = $this->tagPrefix($breed, $year);

        $tags = Animal::query()
            ->where('breed_id', $breed->id)
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

        if (!$existingAnimal) {
            return;
        }

        if ((int) $existingAnimal->breed_id === (int) $breed->id) {
            throw new RuntimeException(
                "The tag {$tagNumber} already exists for {$breed->breed_name}."
            );
        }

        throw new RuntimeException(
            "The tag {$tagNumber} already belongs to another breed. "
            . 'Two breeds with the same first letter cannot use the same '
            . 'yearly tally under the one-letter tag rule.'
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
            throw new RuntimeException(
                'Date of birth is invalid.'
            );
        }

        if ($date->isFuture()) {
            throw new RuntimeException(
                'Date of birth cannot be in the future.'
            );
        }

        return (int) $date->year;
    }

    /**
     * Uses only the first letter of the actual breed name.
     * Species and the Breed Prefix field are ignored.
     */
    private function breedLetter(Breed $breed): string
    {
        $breedName = strtoupper(
            trim((string) $breed->breed_name)
        );

        if (!preg_match('/[A-Z]/', $breedName, $matches)) {
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
