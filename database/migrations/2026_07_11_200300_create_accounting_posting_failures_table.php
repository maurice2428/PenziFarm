<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_posting_failures', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 100)->index('apf_source_type_idx');
            $table->unsignedBigInteger('source_id')->nullable()->index('apf_source_id_idx');
            $table->string('source_action', 50)->default('recognition');
            $table->string('event_name', 80)->nullable();
            $table->string('exception_class')->nullable();
            $table->text('error_message');
            $table->text('stack_excerpt')->nullable();
            $table->string('status', 30)->default('pending')->index('apf_status_idx');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id', 'source_action'], 'apf_source_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_posting_failures');
    }
};
