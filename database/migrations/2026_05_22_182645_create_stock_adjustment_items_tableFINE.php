<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
                // in, out

                $table->decimal('quantity', 15, 3);
                $table->string('unit')->nullable();

                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('line_value', 15, 2)->default(0);

                $table->decimal('stock_before', 15, 3)->default(0);
                $table->decimal('stock_after', 15, 3)->default(0);

                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('stock_adjustment_id', 'stock_adj_item_adj_idx');
                $table->index('inventory_item_id', 'stock_adj_item_inventory_idx');
            });

            return;
        }

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustment_items', 'stock_adjustment_id')) {
                $table->foreignId('stock_adjustment_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('stock_adjustments')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')
                    ->nullable()
                    ->after('stock_adjustment_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'direction')) {
                $table->string('direction')->default('in')->after('inventory_item_id');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'quantity')) {
                $table->decimal('quantity', 15, 3)->default(0)->after('direction');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'unit')) {
                $table->string('unit')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->default(0)->after('unit');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'line_value')) {
                $table->decimal('line_value', 15, 2)->default(0)->after('unit_cost');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'stock_before')) {
                $table->decimal('stock_before', 15, 3)->default(0)->after('line_value');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'stock_after')) {
                $table->decimal('stock_after', 15, 3)->default(0)->after('stock_before');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'notes')) {
                $table->text('notes')->nullable()->after('stock_after');
            }

            if (! Schema::hasColumn('stock_adjustment_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
