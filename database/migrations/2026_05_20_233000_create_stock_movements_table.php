<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->string('movement_no')->unique();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();

            $table->string('direction');
            // in, out, adjustment

            $table->string('type');
            // purchase_receipt, animal_feeding, vet_treatment, crop_input, adjustment

            $table->decimal('quantity', 15, 3);
            $table->string('unit')->nullable();

            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);

            $table->date('movement_date');

            $table->nullableMorphs('referenceable');

            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();

            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['inventory_item_id', 'direction'], 'stock_item_direction_idx');
            $table->index(['type', 'movement_date'], 'stock_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
