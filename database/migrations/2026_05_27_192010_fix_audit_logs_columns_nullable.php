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

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE audit_logs
            MODIFY audit_session_id BIGINT UNSIGNED NULL,
            MODIFY user_id BIGINT UNSIGNED NULL,
            MODIFY user_name VARCHAR(255) NULL,
            MODIFY user_email VARCHAR(255) NULL,
            MODIFY event VARCHAR(255) NOT NULL,
            MODIFY module VARCHAR(255) NULL,
            MODIFY auditable_type VARCHAR(255) NULL,
            MODIFY auditable_id BIGINT UNSIGNED NULL,
            MODIFY record_label VARCHAR(255) NULL,
            MODIFY description TEXT NULL,
            MODIFY old_values JSON NULL,
            MODIFY new_values JSON NULL,
            MODIFY metadata JSON NULL,
            MODIFY severity VARCHAR(255) NOT NULL DEFAULT 'info',
            MODIFY ip_address VARCHAR(255) NULL,
            MODIFY user_agent TEXT NULL,
            MODIFY url TEXT NULL,
            MODIFY route_name VARCHAR(255) NULL,
            MODIFY http_method VARCHAR(255) NULL,
            MODIFY response_status SMALLINT UNSIGNED NULL
        ");
    }

    public function down(): void
    {
        //
    }
};
