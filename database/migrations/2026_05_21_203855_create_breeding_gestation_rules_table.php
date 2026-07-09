<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_gestation_rules', function (Blueprint $table) {
            $table->id();

            $table->string('species')->index(); // Sheep, Goat, Cattle
            $table->foreignId('breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->unsignedSmallInteger('gestation_days');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['species', 'breed_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_gestation_rules');
    }
};
