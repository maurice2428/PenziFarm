<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('animal_feedings')) {
            Schema::create('animal_feedings', function (Blueprint $table) {
                $table->id();

                $table->string('feeding_no')->unique();
                $table->date('feeding_date');

                $table->string('target_type')->default('selected_animals');
                // selected_animals, breed, location, all_active

                $table->foreignId('breed_id')
                    ->nullable()
                    ->constrained('breeds')
                    ->nullOnDelete();

                $table->foreignId('location_id')
                    ->nullable()
                    ->constrained('locations')
                    ->nullOnDelete();

                $table->unsignedInteger('total_animals')->default(0);
                $table->decimal('total_feed_quantity', 15, 3)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);

                $table->text('notes')->nullable();

                $table->foreignId('fed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['feeding_date', 'target_type'], 'feeding_date_target_idx');
            });
        }

        if (! Schema::hasTable('animal_feeding_animal')) {
            Schema::create('animal_feeding_animal', function (Blueprint $table) {
                $table->id();

                $table->foreignId('animal_feeding_id')
                    ->constrained('animal_feedings')
                    ->cascadeOnDelete();

                $table->foreignId('animal_id')
                    ->constrained('animals')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(['animal_feeding_id', 'animal_id'], 'feeding_animal_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_feeding_animal');
        Schema::dropIfExists('animal_feedings');
    }
};
