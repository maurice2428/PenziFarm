<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('animal_feeding_items')) {
            return;
        }

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

            $table->index('animal_feeding_id', 'feed_item_feeding_idx');
            $table->index('inventory_item_id', 'feed_item_inventory_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_feeding_items');
    }
};
