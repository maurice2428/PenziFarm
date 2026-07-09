<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'audit_session_id')) {
                $table
                    ->foreignId('audit_session_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('audit_sessions')
                    ->nullOnDelete()
                    ->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'audit_session_id')) {
                $table->dropConstrainedForeignId('audit_session_id');
            }
        });
    }
};
