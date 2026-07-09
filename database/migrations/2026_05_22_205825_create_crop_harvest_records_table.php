<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_harvest_records')) {
            Schema::create('crop_harvest_records', function (Blueprint $table) {
                $table->id();

                $table->string('harvest_no')->unique();

                $table
                    ->foreignId('crop_season_id')
                    ->constrained('crop_seasons')
                    ->cascadeOnDelete();

                $table->date('harvest_date');

                $table->decimal('quantity', 15, 3)->default(0);
                $table->string('unit')->default('kg');

                $table->decimal('grade_a_quantity', 15, 3)->default(0);
                $table->decimal('grade_b_quantity', 15, 3)->default(0);
                $table->decimal('rejected_quantity', 15, 3)->default(0);

                $table->decimal('unit_value', 15, 2)->default(0);
                $table->decimal('estimated_value', 15, 2)->default(0);

                $table->string('harvested_by')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['harvest_date', 'crop_season_id'], 'crop_harvest_date_season_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_harvest_records');
    }
};
