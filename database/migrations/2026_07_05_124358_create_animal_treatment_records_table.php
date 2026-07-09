<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_treatment_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinical_case_id')
                ->constrained('animal_clinical_cases')
                ->cascadeOnDelete();

            $table->foreignId('animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table->dateTime('given_at')->useCurrent();

            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('method')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();

            $table->decimal('quantity_used', 12, 2)->nullable();

            $table->string('status', 50)->default('Completed');
            $table->string('administered_by')->nullable();

            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();

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

            $table->index(['animal_id', 'given_at']);
            $table->index(['clinical_case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_treatment_records');
    }
};
