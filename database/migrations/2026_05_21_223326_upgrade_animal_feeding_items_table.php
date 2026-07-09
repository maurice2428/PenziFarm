<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('animal_feeding_items')) {
            Schema::create('animal_feeding_items', function (Blueprint $table) {
                $table->id();

                $table->foreignId('animal_feeding_id')
                    ->constrained('animal_feedings')
                    ->cascadeOnDelete();

                $table->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();

                $table->decimal('quantity', 15, 3);
                $table->string('unit')->nullable();

                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);

                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                return;
            });
        }

        Schema::table('animal_feeding_items', function (Blueprint $table) {
            if (! Schema::hasColumn('animal_feeding_items', 'animal_feeding_id')) {
                $table->foreignId('animal_feeding_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('animal_feedings')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('animal_feeding_items', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')
                    ->nullable()
                    ->after('animal_feeding_id')
                    ->constrained('inventory_items')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('animal_feeding_items', 'quantity')) {
                $table->decimal('quantity', 15, 3)->default(0)->after('inventory_item_id');
            }

            if (! Schema::hasColumn('animal_feeding_items', 'unit')) {
                $table->string('unit')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('animal_feeding_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->default(0)->after('unit');
            }

            if (! Schema::hasColumn('animal_feeding_items', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->default(0)->after('unit_cost');
            }

            if (! Schema::hasColumn('animal_feeding_items', 'notes')) {
                $table->text('notes')->nullable()->after('total_cost');
            }

            if (! Schema::hasColumn('animal_feeding_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_feeding_items');
    }
};
