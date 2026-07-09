<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sales_invoice_id');

            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->cascadeOnDelete();

            $table->string('item_type')->default('animal');

            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();

            $table->string('tag_number')->nullable()->index();
            $table->string('breed_name')->nullable();
            $table->string('sex')->nullable();

            $table->text('description')->nullable();

            $table->enum('price_mode', [
                'fixed',
                'per_kg',
                'breeder',
                'manual',
            ])->default('fixed');

            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('sale_weight', 12, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('breeder_premium_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->boolean('is_breeder_sale')->default(false);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['item_type', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
