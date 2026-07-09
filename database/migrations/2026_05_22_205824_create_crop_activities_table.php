<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_activities')) {
            Schema::create('crop_activities', function (Blueprint $table) {
                $table->id();

                $table->string('activity_no')->unique();

                $table
                    ->foreignId('crop_season_id')
                    ->nullable()
                    ->constrained('crop_seasons')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('nursery_batch_id')
                    ->nullable()
                    ->constrained('nursery_batches')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('field_partition_id')
                    ->nullable()
                    ->constrained('field_partitions')
                    ->nullOnDelete();

                $table->date('activity_date');
                $table->string('activity_type');
                // land_preparation, planting, weeding, irrigation, pruning, scouting, spraying, fertilizer, nursery_care, other

                $table->string('growth_stage')->nullable();
                $table->string('performed_by')->nullable();

                $table->string('status')->default('completed');
                // planned, completed, cancelled

                $table->text('description')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['activity_date', 'activity_type'], 'crop_activity_date_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_activities');
    }
};
