<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('crop_catalogs')) {
            Schema::create('crop_catalogs', function (Blueprint $table) {
                $table->id();

                $table->string('crop_code')->unique();
                $table->string('name');
                $table->string('variety')->nullable();

                $table->string('category')->default('general');
                // cereal, fruit_tree, vegetable, fodder, nursery, legume, tuber, general

                $table->string('crop_type')->default('annual');
                // annual, perennial, nursery, orchard

                $table->string('scientific_name')->nullable();
                $table->string('cover_image')->nullable();

                $table->unsignedSmallInteger('germination_days_min')->nullable();
                $table->unsignedSmallInteger('germination_days_max')->nullable();
                $table->unsignedSmallInteger('transplant_days')->nullable();

                $table->unsignedSmallInteger('maturity_days_min')->nullable();
                $table->unsignedSmallInteger('maturity_days_max')->nullable();
                $table->unsignedSmallInteger('harvest_window_days')->nullable();

                $table->decimal('spacing_between_rows_cm', 10, 2)->nullable();
                $table->decimal('spacing_between_plants_cm', 10, 2)->nullable();

                $table->decimal('seed_rate_per_acre', 12, 3)->nullable();
                $table->string('seed_rate_unit')->nullable();

                $table->decimal('expected_yield_per_acre', 12, 3)->nullable();
                $table->string('yield_unit')->nullable();

                $table->string('water_requirement')->nullable();
                $table->string('soil_requirement')->nullable();

                $table->longText('care_routine')->nullable();
                $table->longText('fertilizer_routine')->nullable();
                $table->longText('spray_routine')->nullable();
                $table->longText('harvest_notes')->nullable();

                $table->boolean('is_perennial')->default(false);
                $table->boolean('supports_nursery')->default(false);
                $table->boolean('is_active')->default(true);

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['category', 'crop_type'], 'crop_catalog_category_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_catalogs');
    }
};
