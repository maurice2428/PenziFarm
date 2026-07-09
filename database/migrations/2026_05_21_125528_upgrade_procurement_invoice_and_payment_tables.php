<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->unique()->after('purchase_order_number');
            }

            if (! Schema::hasColumn('purchase_orders', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number')->nullable()->after('invoice_number');
            }

            if (! Schema::hasColumn('purchase_orders', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('order_date');
            }

            if (! Schema::hasColumn('purchase_orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }

            if (! Schema::hasColumn('purchase_orders', 'other_charges')) {
                $table->decimal('other_charges', 14, 2)->default(0)->after('discount_amount');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'line_subtotal')) {
                $table->decimal('line_subtotal', 14, 2)->default(0)->after('unit_cost');
            }

            if (! Schema::hasColumn('purchase_order_items', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0)->after('line_subtotal');
            }

            if (! Schema::hasColumn('purchase_order_items', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('purchase_order_items', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
            }
        });

        if (! Schema::hasTable('purchase_order_payments')) {
            Schema::create('purchase_order_payments', function (Blueprint $table) {
                $table->id();

                $table->foreignId('purchase_order_id')
                    ->constrained('purchase_orders')
                    ->cascadeOnDelete();

                $table->string('payment_number')->unique();
                $table->date('payment_date');

                $table->decimal('amount', 14, 2);
                $table->string('payment_method');
                $table->string('status')->default('successful');

                $table->string('mpesa_reference')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_reference')->nullable();
                $table->string('cheque_number')->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['purchase_order_id', 'status']);
                $table->index(['payment_method', 'payment_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_order_payments')) {
            Schema::dropIfExists('purchase_order_payments');
        }

        Schema::table('purchase_order_items', function (Blueprint $table) {
            foreach (['line_subtotal', 'discount_amount', 'tax_rate', 'tax_amount'] as $column) {
                if (Schema::hasColumn('purchase_order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach (['invoice_number', 'supplier_invoice_number', 'invoice_date', 'due_date', 'other_charges'] as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
