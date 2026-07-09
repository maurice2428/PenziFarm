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
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        if (Schema::hasColumn('stock_movements', 'source')) {
            DB::statement("ALTER TABLE stock_movements MODIFY source VARCHAR(255) NOT NULL");
        }
    }
};
