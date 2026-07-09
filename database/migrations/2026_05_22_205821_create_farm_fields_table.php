<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('farm_fields')) {
            Schema::create('farm_fields', function (Blueprint $table) {
                $table->id();

                $table->string('field_code')->unique();
                $table->string('name');

                $table
                    ->foreignId('location_id')
                    ->nullable()
                    ->constrained('locations')
                    ->nullOnDelete();

                $table->decimal('total_area', 12, 3)->default(0);
                $table->string('area_unit')->default('acre');
                // acre, hectare, sqm

                $table->string('soil_type')->nullable();
                $table->string('irrigation_type')->nullable();
                $table->string('status')->default('active');
                // active, fallow, under_preparation, inactive

                $table->json('map_coordinates')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'area_unit'], 'farm_fields_status_area_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_fields');
    }
};
