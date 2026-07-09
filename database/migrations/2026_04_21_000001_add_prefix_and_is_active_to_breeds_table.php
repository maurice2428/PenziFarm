<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breeds', function (Blueprint $table) {
            if (! Schema::hasColumn('breeds', 'prefix')) {
                $table->string('prefix', 10)->nullable()->after('breed_name');
            }

            if (! Schema::hasColumn('breeds', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('prefix');
            }
        });

        $prefixes = [
            'Dorper' => 'DOR',
            'Red Maasai' => 'RMA',
            'Merino' => 'MER',
            'Suffolk' => 'SUF',
            'Hampshire Down' => 'HAD',
            'Boer' => 'BOE',
            'Galla' => 'GAL',
            'Alpine' => 'ALP',
        ];

        foreach ($prefixes as $breedName => $prefix) {
            DB::table('breeds')
                ->where('breed_name', $breedName)
                ->update([
                    'prefix' => $prefix,
                    'is_active' => 1,
                ]);
        }

        // Only create the unique index if it does not already exist
        $indexExists = DB::select("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'breeds'
              AND index_name = 'breeds_prefix_unique'
            LIMIT 1
        ");

        if (empty($indexExists)) {
            Schema::table('breeds', function (Blueprint $table) {
                $table->unique('prefix', 'breeds_prefix_unique');
            });
        }
    }

    public function down(): void
    {
        $indexExists = DB::select("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'breeds'
              AND index_name = 'breeds_prefix_unique'
            LIMIT 1
        ");

        Schema::table('breeds', function (Blueprint $table) use ($indexExists) {
            if (! empty($indexExists)) {
                $table->dropUnique('breeds_prefix_unique');
            }

            if (Schema::hasColumn('breeds', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('breeds', 'prefix')) {
                $table->dropColumn('prefix');
            }
        });
    }
};
