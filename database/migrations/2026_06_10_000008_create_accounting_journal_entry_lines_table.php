<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('accounting_journal_entries')
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->foreignId('cost_center_id')
                ->nullable()
                ->constrained('accounting_cost_centers')
                ->nullOnDelete();

            $table->foreignId('project_fund_id')
                ->nullable()
                ->constrained('accounting_project_funds')
                ->nullOnDelete();

            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('party_type')->nullable()->index('ajel_party_type_idx');
            $table->unsignedBigInteger('party_id')->nullable()->index('ajel_party_id_idx');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'debit', 'credit'], 'ajel_account_amounts_idx');
            $table->index(['project_fund_id', 'cost_center_id'], 'ajel_project_cost_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_entry_lines');
    }
};
