<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_valuations')) {
            Schema::create('asset_valuations', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('farm_asset_id')
                    ->constrained('farm_assets')
                    ->cascadeOnDelete();

                $table->date('valuation_date');

                $table->string('valuation_type')->default('revaluation');
                // purchase, revaluation, impairment, disposal_estimate, insurance

                $table->decimal('previous_value', 15, 2)->default(0);
                $table->decimal('valuation_amount', 15, 2)->default(0);
                $table->decimal('depreciation_amount', 15, 2)->default(0);

                $table->string('condition')->nullable();
                $table->string('valuer_name')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['farm_asset_id', 'valuation_date'], 'asset_val_asset_date_idx');
            });

            return;
        }

        Schema::table('asset_valuations', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_valuations', 'farm_asset_id')) {
                $table
                    ->foreignId('farm_asset_id')
                    ->nullable()
                    ->constrained('farm_assets')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('asset_valuations', 'valuation_date')) {
                $table->date('valuation_date')->nullable();
            }

            if (!Schema::hasColumn('asset_valuations', 'valuation_type')) {
                $table->string('valuation_type')->default('revaluation');
            }

            if (!Schema::hasColumn('asset_valuations', 'previous_value')) {
                $table->decimal('previous_value', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('asset_valuations', 'valuation_amount')) {
                $table->decimal('valuation_amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('asset_valuations', 'depreciation_amount')) {
                $table->decimal('depreciation_amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('asset_valuations', 'condition')) {
                $table->string('condition')->nullable();
            }

            if (!Schema::hasColumn('asset_valuations', 'valuer_name')) {
                $table->string('valuer_name')->nullable();
            }

            if (!Schema::hasColumn('asset_valuations', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('asset_valuations', 'created_by')) {
                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('asset_valuations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_valuations');
    }
};
