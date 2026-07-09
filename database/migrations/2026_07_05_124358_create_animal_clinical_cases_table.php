<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_clinical_cases', function (Blueprint $table) {
            $table->id();

            $table->string('case_number')->unique();

            $table->foreignId('animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table->dateTime('case_date')->useCurrent();

            $table->string('status', 50)->default('Open');
            $table->string('severity', 30)->default('Moderate');

            $table->text('clinical_signs');
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();

            $table->string('length_of_illness')->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();

            $table->string('animal_source', 50)->nullable();
            $table->string('attending_officer')->nullable();

            $table->text('remarks')->nullable();
            $table->dateTime('resolved_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['animal_id', 'status']);
            $table->index('case_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_clinical_cases');
    }
};
