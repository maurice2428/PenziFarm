<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')->unique();

            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('income_category_id')->nullable()->constrained('income_categories')->nullOnDelete();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->enum('sale_type', [
                'general',
                'breeder',
                'slaughter',
                'commercial',
                'cull',
                'export',
                'other',
            ])->default('general');

            $table->enum('status', [
                'draft',
                'issued',
                'approved',
                'cancelled',
                'voided',
            ])->default('draft');

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
                'overpaid',
                'refunded',
            ])->default('unpaid');

            $table->unsignedInteger('total_animals')->default(0);
            $table->decimal('total_weight', 12, 2)->default(0);
            $table->decimal('average_weight', 12, 2)->default(0);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('other_charges_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);

            $table->text('other_charges_description')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'payment_status']);
            $table->index(['invoice_date', 'due_date']);
            $table->index(['sale_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
