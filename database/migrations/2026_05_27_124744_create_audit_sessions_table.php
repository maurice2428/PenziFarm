<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_sessions')) {
            return;
        }

        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->string('status')->default('active')->index();
            // active, closed

            $table->string('logout_reason')->nullable()->index();
            // logout, expired, forced, system

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
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_sessions');
    }
};
