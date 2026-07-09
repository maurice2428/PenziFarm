<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->enum('type', [
                'vaccination',
                'deworming',
                'treatment',
                'weight',
                'breeding',
                'pregnancy_check',
                'birth',
                'transfer',
                'sale',
                'death',
                'culling',
                'valuation',
            ]);

            $table->date('event_date');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['animal_id', 'type']);
            $table->index(['event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_events');
    }
};
