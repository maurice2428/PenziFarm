<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_source_postings', function (Blueprint $table): void {
            $table->id();
            $table->string('posting_key', 190)->unique('asp_posting_key_uq');
            $table->string('source_type', 100)->index('asp_source_type_idx');
            $table->unsignedBigInteger('source_id')->nullable()->index('asp_source_id_idx');
            $table->string('source_action', 50)->default('recognition')->index('asp_action_idx');
            $table->string('source_reference', 190)->nullable()->index('asp_ref_idx');
            $table->foreignId('journal_entry_id')->nullable()->constrained('accounting_journal_entries')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index('asp_status_idx');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'source_action'], 'asp_source_action_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_source_postings');
    }
};
