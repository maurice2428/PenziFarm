<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('farm_assets')) {
            Schema::create('farm_assets', function (Blueprint $table) {
                $table->id();

                $table->string('asset_number')->unique();
                $table->string('name');
                $table->string('category')->index();
                $table->string('asset_type')->nullable();

                $table->string('tag_number')->nullable();
                $table->string('serial_number')->nullable();

                $table
                    ->foreignId('location_id')
                    ->nullable()
                    ->constrained('locations')
                    ->nullOnDelete();

                $table
                    ->foreignId('supplier_id')
                    ->nullable()
                    ->constrained('suppliers')
                    ->nullOnDelete();

                $table->date('acquisition_date')->nullable();
                $table->decimal('purchase_cost', 15, 2)->default(0);
                $table->decimal('current_value', 15, 2)->default(0);
                $table->decimal('salvage_value', 15, 2)->default(0);

                $table->unsignedInteger('useful_life_months')->default(60);
                $table->string('depreciation_method')->default('straight_line');

                $table->date('last_valuation_date')->nullable();
                $table->date('next_valuation_date')->nullable();

                $table->string('condition')->default('good');
                // excellent, good, fair, poor, damaged, disposed

                $table->string('status')->default('active');
                // active, under_maintenance, idle, disposed, lost

                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['category', 'status'], 'farm_asset_category_status_idx');
                $table->index(['acquisition_date', 'last_valuation_date'], 'farm_asset_age_val_idx');
            });

            return;
        }

        Schema::table('farm_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_assets', 'asset_number')) {
                $table->string('asset_number')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'name')) {
                $table->string('name')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'category')) {
                $table->string('category')->default('general');
            }

            if (!Schema::hasColumn('farm_assets', 'asset_type')) {
                $table->string('asset_type')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'tag_number')) {
                $table->string('tag_number')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'serial_number')) {
                $table->string('serial_number')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'location_id')) {
                $table
                    ->foreignId('location_id')
                    ->nullable()
                    ->constrained('locations')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('farm_assets', 'supplier_id')) {
                $table
                    ->foreignId('supplier_id')
                    ->nullable()
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('farm_assets', 'acquisition_date')) {
                $table->date('acquisition_date')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'purchase_cost')) {
                $table->decimal('purchase_cost', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('farm_assets', 'current_value')) {
                $table->decimal('current_value', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('farm_assets', 'salvage_value')) {
                $table->decimal('salvage_value', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('farm_assets', 'useful_life_months')) {
                $table->unsignedInteger('useful_life_months')->default(60);
            }

            if (!Schema::hasColumn('farm_assets', 'depreciation_method')) {
                $table->string('depreciation_method')->default('straight_line');
            }

            if (!Schema::hasColumn('farm_assets', 'last_valuation_date')) {
                $table->date('last_valuation_date')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'next_valuation_date')) {
                $table->date('next_valuation_date')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'condition')) {
                $table->string('condition')->default('good');
            }

            if (!Schema::hasColumn('farm_assets', 'status')) {
                $table->string('status')->default('active');
            }

            if (!Schema::hasColumn('farm_assets', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('farm_assets', 'created_by')) {
                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('farm_assets', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_assets');
    }
};
