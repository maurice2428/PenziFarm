<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_seasons')) {
            Schema::create('crop_seasons', function (Blueprint $table) {
                $table->id();

                $table->string('season_code')->unique();
                $table->string('name');

                $table
                    ->foreignId('crop_catalog_id')
                    ->constrained('crop_catalogs')
                    ->restrictOnDelete();

                $table
                    ->foreignId('farm_field_id')
                    ->nullable()
                    ->constrained('farm_fields')
                    ->nullOnDelete();

                $table
                    ->foreignId('field_partition_id')
                    ->nullable()
                    ->constrained('field_partitions')
                    ->nullOnDelete();

                $table->string('planting_type')->default('direct_seed');
                // direct_seed, transplant, nursery_transfer, orchard

                $table->date('start_date');
                $table->date('planting_date')->nullable();

                $table->date('expected_germination_from')->nullable();
                $table->date('expected_germination_to')->nullable();
                $table->date('actual_germination_date')->nullable();
                $table->decimal('germination_percent', 5, 2)->nullable();

                $table->date('expected_transplant_date')->nullable();

                $table->date('expected_harvest_from')->nullable();
                $table->date('expected_harvest_to')->nullable();
                $table->date('actual_harvest_start')->nullable();
                $table->date('actual_harvest_end')->nullable();

                $table->decimal('area_planted', 12, 3)->default(0);
                $table->string('area_unit')->default('acre');

                $table->unsignedInteger('plant_population')->nullable();

                $table->string('growth_stage')->default('planned');
                // planned, planted, germination, vegetative, flowering, fruiting, maturity, harvesting, harvested

                $table->string('health_status')->default('good');
                // excellent, good, fair, poor, critical

                $table->string('status')->default('active');
                // planned, active, completed, cancelled, failed

                $table->decimal('total_input_cost', 15, 2)->default(0);
                $table->decimal('total_harvest_quantity', 15, 3)->default(0);
                $table->string('harvest_unit')->nullable();
                $table->decimal('estimated_harvest_value', 15, 2)->default(0);

                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['crop_catalog_id', 'status'], 'crop_season_crop_status_idx');
                $table->index(['expected_harvest_from', 'expected_harvest_to'], 'crop_season_harvest_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_seasons');
    }
};
