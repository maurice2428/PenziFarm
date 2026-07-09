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

            $table->foreignId('female_animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('male_animal_id')->nullable()->constrained('animals')->nullOnDelete();

            $table->date('heat_date')->nullable();
            $table->date('service_date')->nullable();

            $table->enum('service_type', ['Natural', 'Artificial Insemination'])->default('Natural');

            $table->enum('pregnancy_status', ['Pending', 'Confirmed', 'Not Pregnant', 'Unknown'])->default('Pending');
            $table->date('pregnancy_checked_at')->nullable();

            $table->date('expected_birth_date')->nullable();
            $table->date('actual_birth_date')->nullable();

            $table->enum('outcome', ['Pending', 'Successful', 'Failed', 'Aborted', 'Unknown'])->default('Pending');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('performed_by')->nullable();

            $table->timestamps();

            $table->index(['female_animal_id']);
            $table->index(['male_animal_id']);
            $table->index(['pregnancy_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_records');
    }
};
