<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_tax_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('tax_number', 60)->unique();
            $table->foreignId('tax_setting_id')->nullable()->constrained('accounting_tax_settings')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('accounting_journal_entries')->nullOnDelete();
            $table->string('source_type', 100)->nullable()->index('att_source_type_idx');
            $table->unsignedBigInteger('source_id')->nullable()->index('att_source_id_idx');
            $table->date('transaction_date')->index();
            $table->date('tax_point_date')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->string('direction', 30)->index();
            $table->string('tax_code', 50)->index();
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('posted')->index();
            $table->string('party_name')->nullable();
            $table->string('party_pin', 30)->nullable()->index('att_party_pin_idx');
            $table->string('certificate_number', 100)->nullable();
            $table->string('etims_invoice_number', 100)->nullable()->index('att_etims_inv_idx');
            $table->string('etims_control_unit', 100)->nullable();
            $table->string('etims_internal_data', 190)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id', 'tax_code'], 'att_source_tax_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_tax_transactions');
    }
};
