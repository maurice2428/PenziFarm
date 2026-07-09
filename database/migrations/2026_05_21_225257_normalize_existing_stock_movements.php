<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        if (Schema::hasColumn('stock_movements', 'source')) {
            DB::statement("ALTER TABLE stock_movements MODIFY source VARCHAR(255) NULL DEFAULT NULL");
        }

        if (Schema::hasColumn('stock_movements', 'direction')) {
            DB::table('stock_movements')
                ->whereNull('direction')
                ->where('quantity', '<', 0)
                ->update(['direction' => 'out']);

            DB::table('stock_movements')
                ->whereNull('direction')
                ->where('quantity', '>=', 0)
                ->update(['direction' => 'in']);
        }

        if (Schema::hasColumn('stock_movements', 'type') && Schema::hasColumn('stock_movements', 'source')) {
            DB::statement("
                UPDATE stock_movements
                SET type = COALESCE(NULLIF(type, ''), NULLIF(source, ''), 'legacy')
                WHERE type IS NULL OR type = ''
            ");

            DB::statement("
                UPDATE stock_movements
                SET source = COALESCE(NULLIF(source, ''), NULLIF(type, ''), 'legacy')
                WHERE source IS NULL OR source = ''
            ");
        }

        if (Schema::hasColumn('stock_movements', 'quantity')) {
            DB::statement("UPDATE stock_movements SET quantity = ABS(quantity)");
        }

        if (
            Schema::hasColumn('stock_movements', 'total_cost') &&
            Schema::hasColumn('stock_movements', 'quantity') &&
            Schema::hasColumn('stock_movements', 'unit_cost')
        ) {
            DB::statement("UPDATE stock_movements SET total_cost = ABS(quantity) * unit_cost");
        }
    }

    public function down(): void
    {
        //
    }
};
