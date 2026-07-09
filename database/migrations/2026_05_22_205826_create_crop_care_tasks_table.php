<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_care_tasks')) {
            Schema::create('crop_care_tasks', function (Blueprint $table) {
                $table->id();

                $table->string('task_no')->unique();

                $table
                    ->foreignId('crop_catalog_id')
                    ->nullable()
                    ->constrained('crop_catalogs')
                    ->cascadeOnDelete();

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

                $table->date('due_date');

                $table->string('task_type')->default('care');
                // watering, weeding, fertilizer, spraying, pruning, scouting, nursery_care, harvest, other

                $table->string('title');
                $table->text('instructions')->nullable();

                $table->string('status')->default('pending');
                // pending, completed, skipped, cancelled

                $table->timestamp('completed_at')->nullable();

                $table
                    ->foreignId('completed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['due_date', 'status'], 'crop_task_due_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_care_tasks');
    }
};
