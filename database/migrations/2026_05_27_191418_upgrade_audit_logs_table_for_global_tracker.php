<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'audit_session_id')) {
                $table->foreignId('audit_session_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('audit_sessions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_logs', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('audit_session_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_logs', 'user_name')) {
                $table->string('user_name')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('audit_logs', 'user_email')) {
                $table->string('user_email')->nullable()->after('user_name');
            }

            if (! Schema::hasColumn('audit_logs', 'event')) {
                $table->string('event')->index()->after('user_email');
            }

            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module')->nullable()->index()->after('event');
            }

            if (! Schema::hasColumn('audit_logs', 'auditable_type')) {
                $table->string('auditable_type')->nullable()->after('module');
            }

            if (! Schema::hasColumn('audit_logs', 'auditable_id')) {
                $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
            }

            if (! Schema::hasColumn('audit_logs', 'record_label')) {
                $table->string('record_label')->nullable()->after('auditable_id');
            }

            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->text('description')->nullable()->after('record_label');
            }

            if (! Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('description');
            }

            if (! Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }

            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('new_values');
            }

            if (! Schema::hasColumn('audit_logs', 'severity')) {
                $table->string('severity')->default('info')->index()->after('metadata');
            }

            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('severity');
            }

            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('audit_logs', 'url')) {
                $table->text('url')->nullable()->after('user_agent');
            }

            if (! Schema::hasColumn('audit_logs', 'route_name')) {
                $table->string('route_name')->nullable()->after('url');
            }

            if (! Schema::hasColumn('audit_logs', 'http_method')) {
                $table->string('http_method')->nullable()->after('route_name');
            }

            if (! Schema::hasColumn('audit_logs', 'response_status')) {
                $table->unsignedSmallInteger('response_status')->nullable()->after('http_method');
            }

            if (! Schema::hasColumn('audit_logs', 'created_at')) {
                $table->timestamps();
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            } catch (\Throwable) {
                //
            }
        });
    }

    public function down(): void
    {
        //
    }
};
