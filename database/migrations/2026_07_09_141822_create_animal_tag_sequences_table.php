<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_tag_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('breed_id')
                ->constrained('breeds')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('birth_year');
            $table->unsignedInteger('last_sequence')->default(0);

            $table->timestamps();

            $table->unique(
                ['breed_id', 'birth_year'],
                'animal_tag_sequences_breed_year_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_tag_sequences');
    }
};
