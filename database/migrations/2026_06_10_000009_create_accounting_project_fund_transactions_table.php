<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_project_fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();

            $table->foreignId('project_fund_id')
                ->constrained('accounting_project_funds')
                ->cascadeOnDelete();

            $table->foreignId('funding_source_id')
                ->nullable()
                ->constrained('accounting_funding_sources')
                ->nullOnDelete();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('accounting_journal_entries')
                ->nullOnDelete();

            $table->enum('transaction_type', ['receipt', 'allocation', 'expense', 'refund', 'adjustment'])
                ->index('apft_type_idx');

            $table->date('transaction_date')->index('apft_date_idx');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank', 'mpesa', 'petty_cash', 'other'])->default('bank');
            $table->string('reference', 100)->nullable()->index('apft_reference_idx');
            $table->text('narration')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_fund_id', 'transaction_type'], 'apft_project_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_project_fund_transactions');
    }
};
