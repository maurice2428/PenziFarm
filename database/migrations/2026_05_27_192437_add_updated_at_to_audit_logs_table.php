<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (! Schema::hasColumn('audit_logs', 'updated_at')) {
            DB::statement('ALTER TABLE audit_logs ADD updated_at TIMESTAMP NULL AFTER created_at');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (Schema::hasColumn('audit_logs', 'updated_at')) {
            DB::statement('ALTER TABLE audit_logs DROP COLUMN updated_at');
        }
    }
};
