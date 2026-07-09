<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_administration_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_administration_id')->constrained('health_administrations')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['health_administration_id', 'animal_id'], 'health_admin_animal_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_administration_animals');
    }
};
