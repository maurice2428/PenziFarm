<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_input_applications')) {
            Schema::create('crop_input_applications', function (Blueprint $table) {
                $table->id();

                $table->string('application_no')->unique();

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

                $table
                    ->foreignId('inventory_item_id')
                    ->constrained('inventory_items')
                    ->restrictOnDelete();

                $table->date('application_date');

                $table->string('application_type');
                // seed, fertilizer, chemical, manure, irrigation, nursery_media, other

                $table->decimal('quantity', 15, 3);
                $table->string('unit')->nullable();

                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);

                $table->decimal('target_area', 12, 3)->nullable();
                $table->string('area_unit')->nullable();

                $table->string('method')->nullable();
                // broadcast, drip, foliar, soil_drench, spray, manual, other

                $table->string('applied_by')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['application_date', 'application_type'], 'crop_input_date_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_input_applications');
    }
};
