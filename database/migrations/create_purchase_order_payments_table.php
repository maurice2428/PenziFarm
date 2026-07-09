<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_payments')) {
            return;
        }

        Schema::create('purchase_order_payments', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->string('payment_number')->unique();
            $table->date('payment_date');

            $table->decimal('amount', 14, 2);
            $table->string('payment_method');  // cash, bank, mpesa_b2b, cheque
            $table->string('status')->default('successful');  // pending, successful, failed, reversed

            $table->string('mpesa_reference')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('cheque_number')->nullable();

            $table->text('notes')->nullable();

            $table
                ->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_order_id', 'status']);
            $table->index(['payment_method', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_payments');
    }
};
