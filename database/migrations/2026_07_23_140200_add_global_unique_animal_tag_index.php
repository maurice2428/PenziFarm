<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $indexName = 'animals_tag_number_global_unique';

    public function up(): void
    {
        if (
            ! Schema::hasTable('animals')
            || ! Schema::hasColumn('animals', 'tag_number')
        ) {
            return;
        }

        $duplicate = DB::table('animals')
            ->select('tag_number')
            ->whereNotNull('tag_number')
            ->groupBy('tag_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            logger()->warning(
                'Global animal tag unique index was not added because duplicate tags already exist.',
                ['example_duplicate' => $duplicate->tag_number]
            );

            return;
        }

        if ($this->indexExists()) {
            return;
        }

        Schema::table('animals', function (Blueprint $table): void {
            $table->unique('tag_number', $this->indexName);
        });
    }

    public function down(): void
    {
        if (! $this->indexExists()) {
            return;
        }

        Schema::table('animals', function (Blueprint $table): void {
            $table->dropUnique($this->indexName);
        });
    }

    private function indexExists(): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'animals')
            ->where('index_name', $this->indexName)
            ->exists();
    }
};
