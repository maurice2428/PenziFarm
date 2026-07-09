<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

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
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
