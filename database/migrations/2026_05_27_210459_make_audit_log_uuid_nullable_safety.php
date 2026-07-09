<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('audit_logs', 'uuid')) {
                DB::statement('ALTER TABLE audit_logs MODIFY uuid VARCHAR(255) NULL');
            }

            if (Schema::hasColumn('audit_logs', 'audit_session_uuid')) {
                DB::statement('ALTER TABLE audit_logs MODIFY audit_session_uuid VARCHAR(255) NULL');
            }

            if (Schema::hasColumn('audit_logs', 'batch_uuid')) {
                DB::statement('ALTER TABLE audit_logs MODIFY batch_uuid VARCHAR(255) NULL');
            }
        }
    }

    public function down(): void
    {
        //
    }
};
