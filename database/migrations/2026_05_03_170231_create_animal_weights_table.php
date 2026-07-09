<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_weights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('animal_id')
                ->constrained('animals')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('weight_kg', 8, 2);

            $table->dateTime('recorded_at');

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_weights');
    }
};
