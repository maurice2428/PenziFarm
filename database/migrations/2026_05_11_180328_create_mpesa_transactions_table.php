<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_payment_id')->nullable()->constrained('sales_payments')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable()->unique();

            $table->string('phone_number');
            $table->decimal('amount', 15, 2);
            $table->string('account_reference')->nullable();
            $table->string('transaction_desc')->nullable();

            $table->string('mpesa_receipt_number')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();

            $table->enum('status', ['pending', 'successful', 'failed', 'cancelled'])->default('pending');

            $table->json('request_payload')->nullable();
            $table->json('callback_payload')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }
};
