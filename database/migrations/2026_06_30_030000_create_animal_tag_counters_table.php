<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('animal_tag_counters')) {
            Schema::create('animal_tag_counters', function (Blueprint $table) {
                $table->id();

                $table->foreignId('breed_id')
                    ->constrained('breeds')
                    ->cascadeOnDelete();

                $table->unsignedSmallInteger('birth_year');

                $table->unsignedInteger('last_number')->default(0);

                $table->timestamps();

                $table->unique(
                    ['breed_id', 'birth_year'],
                    'animal_tag_counters_breed_year_unique'
                );
            });
        }

        /*
         * Physical ear tags must remain unique across the farm.
         * The old per-breed unique index remains valid too.
         */
        if (
            Schema::hasTable('animals') &&
            ! $this->indexExists('animals', 'animals_tag_number_global_unique')
        ) {
            Schema::table('animals', function (Blueprint $table) {
                $table->unique(
                    'tag_number',
                    'animals_tag_number_global_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('animals') &&
            $this->indexExists('animals', 'animals_tag_number_global_unique')
        ) {
            Schema::table('animals', function (Blueprint $table) {
                $table->dropUnique('animals_tag_number_global_unique');
            });
        }

        Schema::dropIfExists('animal_tag_counters');
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
