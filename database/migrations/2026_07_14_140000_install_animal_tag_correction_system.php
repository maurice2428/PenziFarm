<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('animal_tag_prefix_counters')) {
            Schema::create(
                'animal_tag_prefix_counters',
                function (Blueprint $table): void {
                    $table->id();
                    $table->string('tag_prefix', 100)->unique();
                    $table->unsignedInteger('last_number')->default(0);
                    $table->timestamps();
                }
            );
        }

        if (! Schema::hasTable('animal_tag_corrections')) {
            Schema::create(
                'animal_tag_corrections',
                function (Blueprint $table): void {
                    $table->id();

                    $table->foreignId('animal_id')
                        ->nullable()
                        ->constrained('animals')
                        ->nullOnDelete();

                    $table->string('old_tag_number', 255);
                    $table->string('new_tag_number', 255);

                    $table->foreignId('old_breed_id')
                        ->nullable()
                        ->constrained('breeds')
                        ->nullOnDelete();

                    $table->foreignId('new_breed_id')
                        ->nullable()
                        ->constrained('breeds')
                        ->nullOnDelete();

                    $table->date('old_date_of_birth')->nullable();
                    $table->date('new_date_of_birth')->nullable();
                    $table->string('correction_type', 150);
                    $table->text('reason');

                    $table->foreignId('corrected_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();

                    $table->timestamps();

                    $table->index('animal_id');
                    $table->index('old_tag_number');
                    $table->index('new_tag_number');
                }
            );
        } else {
            $this->ensureExistingCorrectionTableColumns();
        }

        $this->addGlobalTagUniqueIndexWhenSafe();
    }

    public function down(): void
    {
        if (
            Schema::hasTable('animals')
            && Schema::hasIndex(
                'animals',
                'animals_tag_number_unique'
            )
        ) {
            Schema::table('animals', function (Blueprint $table): void {
                $table->dropUnique('animals_tag_number_unique');
            });
        }

        Schema::dropIfExists('animal_tag_prefix_counters');

        /*
         * Correction history is intentionally retained on rollback. It is an
         * audit table and should not be deleted automatically.
         */
    }


    private function ensureExistingCorrectionTableColumns(): void
    {
        $columns = [
            'animal_id' => fn (Blueprint $table) =>
                $table->unsignedBigInteger('animal_id')->nullable(),
            'old_tag_number' => fn (Blueprint $table) =>
                $table->string('old_tag_number', 255)->nullable(),
            'new_tag_number' => fn (Blueprint $table) =>
                $table->string('new_tag_number', 255)->nullable(),
            'old_breed_id' => fn (Blueprint $table) =>
                $table->unsignedBigInteger('old_breed_id')->nullable(),
            'new_breed_id' => fn (Blueprint $table) =>
                $table->unsignedBigInteger('new_breed_id')->nullable(),
            'old_date_of_birth' => fn (Blueprint $table) =>
                $table->date('old_date_of_birth')->nullable(),
            'new_date_of_birth' => fn (Blueprint $table) =>
                $table->date('new_date_of_birth')->nullable(),
            'correction_type' => fn (Blueprint $table) =>
                $table->string('correction_type', 150)->nullable(),
            'reason' => fn (Blueprint $table) =>
                $table->text('reason')->nullable(),
            'corrected_by' => fn (Blueprint $table) =>
                $table->unsignedBigInteger('corrected_by')->nullable(),
            'created_at' => fn (Blueprint $table) =>
                $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) =>
                $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('animal_tag_corrections', $column)) {
                continue;
            }

            Schema::table(
                'animal_tag_corrections',
                function (Blueprint $table) use ($definition): void {
                    $definition($table);
                }
            );
        }
    }

    private function addGlobalTagUniqueIndexWhenSafe(): void
    {
        if (
            ! Schema::hasTable('animals')
            || ! Schema::hasColumn('animals', 'tag_number')
        ) {
            return;
        }

        $hasUniqueTagIndex = collect(
            Schema::getIndexes('animals')
        )->contains(function (array $index): bool {
            $columns = array_values($index['columns'] ?? []);

            return (bool) ($index['unique'] ?? false)
                && $columns === ['tag_number'];
        });

        if ($hasUniqueTagIndex) {
            return;
        }

        $hasDuplicates = DB::table('animals')
            ->select('tag_number')
            ->whereNotNull('tag_number')
            ->groupBy('tag_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            logger()->warning(
                'Skipped animals.tag_number global unique index because duplicate tags already exist.'
            );

            return;
        }

        Schema::table('animals', function (Blueprint $table): void {
            $table->unique(
                'tag_number',
                'animals_tag_number_unique'
            );
        });
    }
};
