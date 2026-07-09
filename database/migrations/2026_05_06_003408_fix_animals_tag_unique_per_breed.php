<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            // Remove old global unique constraint
            $table->dropUnique('animals_tag_number_unique');

            // Add new per-breed unique constraint
            $table->unique(['breed_id', 'tag_number'], 'animals_breed_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique('animals_breed_tag_unique');

            $table->unique('tag_number', 'animals_tag_number_unique');
        });
    }
};
