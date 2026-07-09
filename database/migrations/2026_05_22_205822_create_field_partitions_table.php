<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('field_partitions')) {
            Schema::create('field_partitions', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('farm_field_id')
                    ->constrained('farm_fields')
                    ->cascadeOnDelete();

                $table->string('partition_code')->unique();
                $table->string('name');

                $table->decimal('area', 12, 3)->default(0);
                $table->string('area_unit')->default('acre');

                $table->string('status')->default('vacant');
                // vacant, under_preparation, planted, nursery, orchard, harvested, fallow

                $table->json('map_coordinates')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['farm_field_id', 'status'], 'field_partition_field_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_partitions');
    }
};
