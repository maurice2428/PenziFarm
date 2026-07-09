<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->id();

            $table->string('receipt_no')->unique();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->date('received_date');

            $table->string('delivery_note_no')->nullable();
            $table->string('supplier_invoice_no')->nullable();

            $table->string('status')->default('received');
            // received, partial, cancelled

            $table->decimal('total_accepted_quantity', 15, 3)->default(0);
            $table->decimal('total_rejected_quantity', 15, 3)->default(0);
            $table->decimal('total_received_value', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_order_id', 'received_date'], 'po_receipt_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipts');
    }
};
