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
            Schema::create('animal_tag_prefix_counters', function (Blueprint $table): void {
                $table->id();
                $table->string('tag_prefix', 100)->unique();
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('animal_tag_corrections')) {
            Schema::create('animal_tag_corrections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('animal_id')
                    ->constrained('animals')
                    ->cascadeOnDelete();
                $table->string('old_tag_number');
                $table->string('new_tag_number');
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
                $table->string('correction_type', 100)
                    ->default('tag_sequence');
                $table->text('reason');
                $table->foreignId('corrected_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['animal_id', 'created_at']);
                $table->index('old_tag_number');
                $table->index('new_tag_number');
            });
        }

        $this->seedPrefixCounters();
        $this->tryAddGlobalUniqueTagIndex();
    }

    public function down(): void
    {
        // Audit and sequence data are intentionally preserved.
    }

    private function seedPrefixCounters(): void
    {
        if (
            ! Schema::hasTable('animals')
            || ! Schema::hasColumn('animals', 'tag_number')
            || ! Schema::hasColumn('animals', 'tag_sequence')
        ) {
            return;
        }

        DB::table('animals')
            ->select(['id', 'tag_number', 'tag_sequence'])
            ->whereNotNull('tag_number')
            ->whereNotNull('tag_sequence')
            ->orderBy('id')
            ->chunkById(500, function ($animals): void {
                foreach ($animals as $animal) {
                    $tag = strtoupper(trim((string) $animal->tag_number));
                    $sequence = (int) $animal->tag_sequence;

                    if ($tag === '' || $sequence < 1) {
                        continue;
                    }

                    $suffixes = [
                        str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                        (string) $sequence,
                    ];

                    $prefix = null;

                    foreach ($suffixes as $suffix) {
                        if (str_ends_with($tag, $suffix)) {
                            $prefix = substr($tag, 0, -strlen($suffix));
                            break;
                        }
                    }

                    if (! $prefix) {
                        continue;
                    }

                    $existing = DB::table('animal_tag_prefix_counters')
                        ->where('tag_prefix', $prefix)
                        ->first();

                    if (! $existing) {
                        DB::table('animal_tag_prefix_counters')->insert([
                            'tag_prefix' => $prefix,
                            'last_number' => $sequence,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    if ((int) $existing->last_number < $sequence) {
                        DB::table('animal_tag_prefix_counters')
                            ->where('id', $existing->id)
                            ->update([
                                'last_number' => $sequence,
                                'updated_at' => now(),
                            ]);
                    }
                }
            }, 'id');
    }

    private function tryAddGlobalUniqueTagIndex(): void
    {
        if (! Schema::hasTable('animals')) {
            return;
        }

        $duplicatesExist = DB::table('animals')
            ->select('tag_number')
            ->whereNotNull('tag_number')
            ->groupBy('tag_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicatesExist) {
            return;
        }

        try {
            Schema::table('animals', function (Blueprint $table): void {
                $table->unique(
                    'tag_number',
                    'animals_tag_number_global_unique'
                );
            });
        } catch (\Throwable) {
            // The index may already exist under this or another name.
        }
    }
};
