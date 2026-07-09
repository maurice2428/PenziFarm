<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_lab_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number')->unique();

            $table->foreignId('animal_id')
                ->constrained('animals')
                ->restrictOnDelete();

            $table->foreignId('clinical_case_id')
                ->nullable()
                ->constrained('animal_clinical_cases')
                ->nullOnDelete();

            $table->string('status', 50)->default('Requested');

            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('sample_collected_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('testing_date')->nullable();
            $table->dateTime('resulted_at')->nullable();

            $table->json('specimens')->nullable();
            $table->string('testing_purpose')->nullable();
            $table->json('requested_tests')->nullable();

            $table->string('clinic_name')->nullable();

            $table->text('clinical_signs')->nullable();
            $table->string('length_of_illness')->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->string('animal_source', 50)->nullable();

            $table->string('attending_officer')->nullable();
            $table->text('notes')->nullable();

            $table->text('results')->nullable();
            $table->text('recommended_medication')->nullable();

            $table->string('lab_report_path')->nullable();

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
            $table->index(['clinical_case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_lab_requests');
    }
};
