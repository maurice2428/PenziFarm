<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
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

            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'movement_no')) {
                $table->string('movement_no')->nullable()->after('id');
            }

            if (! Schema::hasColumn('stock_movements', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')
                    ->nullable()
                    ->after('movement_no')
                    ->constrained('inventory_items')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'direction')) {
                $table->string('direction')->default('in')->after('inventory_item_id');
            }

            if (! Schema::hasColumn('stock_movements', 'type')) {
                $table->string('type')->default('adjustment')->after('direction');
            }

            if (! Schema::hasColumn('stock_movements', 'quantity')) {
                $table->decimal('quantity', 15, 3)->default(0)->after('type');
            }

            if (! Schema::hasColumn('stock_movements', 'unit')) {
                $table->string('unit')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->default(0)->after('unit');
            }

            if (! Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->default(0)->after('unit_cost');
            }

            if (! Schema::hasColumn('stock_movements', 'movement_date')) {
                $table->date('movement_date')->nullable()->after('total_cost');
            }

            if (
                ! Schema::hasColumn('stock_movements', 'referenceable_type')
                && ! Schema::hasColumn('stock_movements', 'referenceable_id')
            ) {
                $table->nullableMorphs('referenceable');
            }

            if (! Schema::hasColumn('stock_movements', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->constrained('purchase_orders')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'batch_number')) {
                $table->string('batch_number')->nullable();
            }

            if (! Schema::hasColumn('stock_movements', 'expiry_date')) {
                $table->date('expiry_date')->nullable();
            }

            if (! Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('stock_movements', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
