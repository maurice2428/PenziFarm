<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();

                $table->string('adjustment_no')->unique();
                $table->date('adjustment_date');

                $table->string('reason')
                    ->default('manual_correction');

                $table->decimal('total_in_quantity', 15, 3)->default(0);
                $table->decimal('total_out_quantity', 15, 3)->default(0);
                $table->decimal('total_value', 15, 2)->default(0);

                $table->text('notes')->nullable();

                $table->foreignId('adjusted_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['adjustment_date', 'reason'],
                    'stock_adj_date_reason_idx'
                );
            });
        }

        if (! Schema::hasTable('stock_adjustment_items')) {
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
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
