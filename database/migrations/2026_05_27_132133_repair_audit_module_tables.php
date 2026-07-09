<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Repair audit_sessions
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasTable('audit_sessions')) {
            Schema::create('audit_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('user_name')->nullable();
                $table->string('user_email')->nullable();

                $table->string('status')->default('active')->index();
                $table->string('logout_reason')->nullable()->index();

                $table->timestamp('login_at')->nullable()->index();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('logout_at')->nullable()->index();

                $table->unsignedInteger('request_count')->default(0);
                $table->unsignedInteger('event_count')->default(0);

                $table->string('first_url')->nullable();
                $table->string('last_url')->nullable();

                $table->string('ip_address', 80)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('guard')->nullable();

                $table->text('summary')->nullable();

                $table->string('email_to')->nullable();
                $table->timestamp('emailed_at')->nullable();

                $table->timestamps();

                $table->index(['user_id', 'status'], 'audit_session_user_status_idx');
                $table->index(['status', 'expires_at'], 'audit_session_status_expires_idx');
            });
        } else {
            Schema::table('audit_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_sessions', 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique()->after('id');
                }

                if (! Schema::hasColumn('audit_sessions', 'user_id')) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('uuid')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('audit_sessions', 'user_name')) {
                    $table->string('user_name')->nullable()->after('user_id');
                }

                if (! Schema::hasColumn('audit_sessions', 'user_email')) {
                    $table->string('user_email')->nullable()->after('user_name');
                }

                if (! Schema::hasColumn('audit_sessions', 'status')) {
                    $table->string('status')->default('active')->index()->after('user_email');
                }

                if (! Schema::hasColumn('audit_sessions', 'logout_reason')) {
                    $table->string('logout_reason')->nullable()->index()->after('status');
                }

                if (! Schema::hasColumn('audit_sessions', 'login_at')) {
                    $table->timestamp('login_at')->nullable()->index()->after('logout_reason');
                }

                if (! Schema::hasColumn('audit_sessions', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->index()->after('login_at');
                }

                if (! Schema::hasColumn('audit_sessions', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->index()->after('last_seen_at');
                }

                if (! Schema::hasColumn('audit_sessions', 'logout_at')) {
                    $table->timestamp('logout_at')->nullable()->index()->after('expires_at');
                }

                if (! Schema::hasColumn('audit_sessions', 'request_count')) {
                    $table->unsignedInteger('request_count')->default(0)->after('logout_at');
                }

                if (! Schema::hasColumn('audit_sessions', 'event_count')) {
                    $table->unsignedInteger('event_count')->default(0)->after('request_count');
                }

                if (! Schema::hasColumn('audit_sessions', 'first_url')) {
                    $table->string('first_url')->nullable()->after('event_count');
                }

                if (! Schema::hasColumn('audit_sessions', 'last_url')) {
                    $table->string('last_url')->nullable()->after('first_url');
                }

                if (! Schema::hasColumn('audit_sessions', 'ip_address')) {
                    $table->string('ip_address', 80)->nullable()->after('last_url');
                }

                if (! Schema::hasColumn('audit_sessions', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }

                if (! Schema::hasColumn('audit_sessions', 'guard')) {
                    $table->string('guard')->nullable()->after('user_agent');
                }

                if (! Schema::hasColumn('audit_sessions', 'summary')) {
                    $table->text('summary')->nullable()->after('guard');
                }

                if (! Schema::hasColumn('audit_sessions', 'email_to')) {
                    $table->string('email_to')->nullable()->after('summary');
                }

                if (! Schema::hasColumn('audit_sessions', 'emailed_at')) {
                    $table->timestamp('emailed_at')->nullable()->after('email_to');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Repair audit_logs
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')->nullable()->unique();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('user_name')->nullable();
                $table->string('user_email')->nullable();

                $table->string('event')->index();
                $table->string('module')->nullable()->index();
                $table->string('severity')->default('info')->index();

                $table->nullableMorphs('auditable');
                $table->string('auditable_label')->nullable();

                $table->longText('description')->nullable();

                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();

                $table->string('route_name')->nullable();
                $table->string('url')->nullable();
                $table->string('http_method', 20)->nullable();
                $table->unsignedSmallInteger('response_status')->nullable();

                $table->string('ip_address', 80)->nullable();
                $table->text('user_agent')->nullable();

                $table->string('guard')->nullable();

                $table->uuid('batch_uuid')->nullable()->index();
                $table->uuid('audit_session_uuid')->nullable()->index();

                $table->timestamp('created_at')->nullable();

                $table->index(['user_id', 'created_at'], 'audit_user_date_idx');
                $table->index(['event', 'created_at'], 'audit_event_date_idx');
                $table->index(['module', 'created_at'], 'audit_module_date_idx');
                $table->index(['audit_session_uuid', 'created_at'], 'audit_session_date_idx');
            });
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique()->after('id');
                }

                if (! Schema::hasColumn('audit_logs', 'user_id')) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('uuid')
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
                    $table->string('event')->default('system')->index()->after('user_email');
                }

                if (! Schema::hasColumn('audit_logs', 'module')) {
                    $table->string('module')->nullable()->index()->after('event');
                }

                if (! Schema::hasColumn('audit_logs', 'severity')) {
                    $table->string('severity')->default('info')->index()->after('module');
                }

                if (! Schema::hasColumn('audit_logs', 'auditable_type')) {
                    $table->string('auditable_type')->nullable()->after('severity');
                }

                if (! Schema::hasColumn('audit_logs', 'auditable_id')) {
                    $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
                }

                if (! Schema::hasColumn('audit_logs', 'auditable_label')) {
                    $table->string('auditable_label')->nullable()->after('auditable_id');
                }

                if (! Schema::hasColumn('audit_logs', 'description')) {
                    $table->longText('description')->nullable()->after('auditable_label');
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

                if (! Schema::hasColumn('audit_logs', 'route_name')) {
                    $table->string('route_name')->nullable()->after('metadata');
                }

                if (! Schema::hasColumn('audit_logs', 'url')) {
                    $table->string('url')->nullable()->after('route_name');
                }

                if (! Schema::hasColumn('audit_logs', 'http_method')) {
                    $table->string('http_method', 20)->nullable()->after('url');
                }

                if (! Schema::hasColumn('audit_logs', 'response_status')) {
                    $table->unsignedSmallInteger('response_status')->nullable()->after('http_method');
                }

                if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                    $table->string('ip_address', 80)->nullable()->after('response_status');
                }

                if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                }

                if (! Schema::hasColumn('audit_logs', 'guard')) {
                    $table->string('guard')->nullable()->after('user_agent');
                }

                if (! Schema::hasColumn('audit_logs', 'batch_uuid')) {
                    $table->uuid('batch_uuid')->nullable()->index()->after('guard');
                }

                if (! Schema::hasColumn('audit_logs', 'audit_session_uuid')) {
                    $table->uuid('audit_session_uuid')->nullable()->index()->after('batch_uuid');
                }

                if (! Schema::hasColumn('audit_logs', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->after('audit_session_uuid');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Repair audit_settings
        |--------------------------------------------------------------------------
        */
        if (! Schema::hasTable('audit_settings')) {
            Schema::create('audit_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('type')->default('string');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('audit_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_settings', 'key')) {
                    $table->string('key')->nullable()->unique()->after('id');
                }

                if (! Schema::hasColumn('audit_settings', 'value')) {
                    $table->longText('value')->nullable()->after('key');
                }

                if (! Schema::hasColumn('audit_settings', 'type')) {
                    $table->string('type')->default('string')->after('value');
                }

                if (! Schema::hasColumn('audit_settings', 'description')) {
                    $table->text('description')->nullable()->after('type');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill UUIDs
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('audit_sessions') && Schema::hasColumn('audit_sessions', 'uuid')) {
            DB::table('audit_sessions')
                ->whereNull('uuid')
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($row): void {
                    DB::table('audit_sessions')
                        ->where('id', $row->id)
                        ->update([
                            'uuid' => (string) Str::uuid(),
                            'status' => 'closed',
                            'logout_reason' => 'system',
                        ]);
                });
        }

        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'uuid')) {
            DB::table('audit_logs')
                ->whereNull('uuid')
                ->orderBy('id')
                ->get(['id'])
                ->each(function ($row): void {
                    DB::table('audit_logs')
                        ->where('id', $row->id)
                        ->update([
                            'uuid' => (string) Str::uuid(),
                            'event' => 'system',
                            'severity' => 'info',
                            'created_at' => now('Africa/Nairobi'),
                        ]);
                });
        }
    }

    public function down(): void
    {
        // Intentionally left empty.
        // This is a repair migration and should not remove audit data.
    }
};
