<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('breeding_batch_id')
                ->constrained('breeding_batches')
                ->cascadeOnDelete();

            $table->foreignId('female_animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table->foreignId('male_animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table->foreignId('female_breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->foreignId('male_breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->string('species')->nullable();
            $table->string('breeding_type')->default('natural');

            $table->boolean('is_cross_breed')->default(false);

            $table->date('mating_date');
            $table->unsignedSmallInteger('gestation_days');
            $table->date('expected_due_date');

            $table->string('inbreeding_status')->default('clear');
            // clear, warning, blocked

            $table->text('relationship_notes')->nullable();

            $table->string('pregnancy_status')->default('pending');
            // pending, confirmed, not_pregnant, delivered, aborted

            $table->date('pregnancy_checked_at')->nullable();
            $table->date('delivery_date')->nullable();
            $table->unsignedInteger('offspring_count')->nullable();
            $table->text('delivery_notes')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['breeding_batch_id', 'female_animal_id'], 'breeding_batch_female_unique');
            $table->index(['female_animal_id', 'pregnancy_status']);
            $table->index(['expected_due_date', 'pregnancy_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_records');
    }
};
