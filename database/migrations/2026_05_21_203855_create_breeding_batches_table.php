<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('breeding_batches', function (Blueprint $table) {
            $table->id();

            $table->string('batch_number');
            $table->string('name');

            $table->string('breeding_type')->default('natural');
            // natural, artificial_insemination, embryo_transfer

            $table
                ->foreignId('male_animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table
                ->foreignId('male_breed_id')
                ->nullable()
                ->constrained('breeds')
                ->nullOnDelete();

            $table->string('species')->nullable();
            $table->boolean('allow_cross_breeding')->default(false);

            $table->date('mating_date');
            $table->date('expected_due_from')->nullable();
            $table->date('expected_due_to')->nullable();

            $table->unsignedInteger('total_females')->default(0);

            $table->string('status')->default('recorded');
            // recorded, pregnancy_check, delivered, cancelled

            $table->text('notes')->nullable();

            $table
                ->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            /*
             * |--------------------------------------------------------------------------
             * | Short custom index names
             * |--------------------------------------------------------------------------
             * | MariaDB/MySQL can fail when Laravel auto-generates very long index names.
             */
            $table->unique('batch_number', 'brd_batch_no_unique');

            $table->index(['breeding_type', 'status'], 'brd_batch_type_status_idx');

            $table->index(
                ['mating_date', 'expected_due_from', 'expected_due_to'],
                'brd_batch_due_idx'
            );

            $table->index('male_animal_id', 'brd_batch_male_idx');
            $table->index('male_breed_id', 'brd_batch_male_breed_idx');
            $table->index('created_by', 'brd_batch_created_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_batches');
    }
};
