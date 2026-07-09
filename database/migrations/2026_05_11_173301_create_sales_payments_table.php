<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number')->unique();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->date('payment_date');

            $table->enum('payment_method', [
                'mpesa_stk',
                'mpesa_paybill',
                'bank_transfer',
                'cash',
                'cheque',
                'other',
            ])->default('cash');

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
                'cancelled',
                'reversed',
            ])->default('successful');

            $table->decimal('amount', 15, 2)->default(0);

            $table->string('reference_number')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('paid_by_name')->nullable();
            $table->string('paid_by_phone')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_method', 'status']);
            $table->index(['payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};
