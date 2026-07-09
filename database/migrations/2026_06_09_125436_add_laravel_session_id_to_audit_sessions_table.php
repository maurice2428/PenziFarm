<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_sessions')) {
            return;
        }

        Schema::table('audit_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_sessions', 'laravel_session_id')) {
                $table
                    ->string('laravel_session_id')
                    ->nullable()
                    ->after('uuid')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_sessions')) {
            return;
        }

        Schema::table('audit_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('audit_sessions', 'laravel_session_id')) {
                $table->dropColumn('laravel_session_id');
            }
        });
    }
};
