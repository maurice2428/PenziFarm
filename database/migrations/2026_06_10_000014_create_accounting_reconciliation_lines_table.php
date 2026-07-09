<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_reconciliation_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reconciliation_id');
            $table->unsignedBigInteger('journal_entry_line_id')->nullable();

            $table->date('transaction_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('statement_amount', 15, 2)->default(0);
            $table->boolean('is_matched')->default(false);
            $table->timestamp('matched_at')->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('reconciliation_id', 'arl_recon_idx');
            $table->index('journal_entry_line_id', 'arl_jel_idx');
            $table->index('transaction_date', 'arl_date_idx');
            $table->index('is_matched', 'arl_matched_idx');
            $table->index('matched_by', 'arl_matched_by_idx');

            $table->foreign('reconciliation_id', 'arl_recon_fk')
                ->references('id')
                ->on('accounting_reconciliations')
                ->cascadeOnDelete();

            $table->foreign('matched_by', 'arl_matched_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_reconciliation_lines');
    }
};
