<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_adjustment_items')) {
            return;
        }

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_adjustment_id')
                ->constrained('stock_adjustments')
                ->cascadeOnDelete();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();

            $table->string('direction');
            $table->decimal('quantity', 15, 3);
            $table->string('unit')->nullable();

            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_value', 15, 2)->default(0);

            $table->decimal('stock_before', 15, 3)->default(0);
            $table->decimal('stock_after', 15, 3)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
             * foreignId()->constrained() already creates indexes for the
             * foreign-key columns, so separate indexes are unnecessary.
             */
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
