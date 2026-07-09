<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_tag_corrections', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('animal_id')
                ->constrained('animals')
                ->cascadeOnDelete();

            $table->string('old_tag_number');
            $table->string('new_tag_number')->nullable();

            $table->foreignId('old_breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->foreignId('new_breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->date('old_date_of_birth')->nullable();
            $table->date('new_date_of_birth')->nullable();

            $table->string('correction_type', 50);
            $table->text('reason');

            $table->foreignId('corrected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('old_tag_number');
            $table->index('new_tag_number');
            $table->index(['animal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_tag_corrections');
    }
};
