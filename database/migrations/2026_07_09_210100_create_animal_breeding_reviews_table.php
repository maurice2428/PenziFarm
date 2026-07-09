<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('animal_breeding_reviews')) {
            return;
        }

        Schema::create('animal_breeding_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->restrictOnDelete();
            $table->string('recommendation');
            $table->string('source')->default('manual');
            $table->decimal('performance_score', 6, 2)->nullable();
            $table->text('reason')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();

            $table->index(['animal_id', 'reviewed_at'], 'animal_breeding_review_lookup');
            $table->index(['recommendation', 'reviewed_at'], 'animal_breeding_recommendation_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_breeding_reviews');
    }
};
