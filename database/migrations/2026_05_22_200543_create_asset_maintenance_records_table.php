<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('asset_maintenance_records')) {
            Schema::create('asset_maintenance_records', function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('farm_asset_id')
                    ->constrained('farm_assets')
                    ->cascadeOnDelete();

                $table->date('maintenance_date');

                $table->string('maintenance_type')->default('routine');
                // routine, repair, service, inspection, replacement

                $table->decimal('cost', 15, 2)->default(0);
                $table->string('performed_by')->nullable();
                $table->date('next_service_date')->nullable();
                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['farm_asset_id', 'maintenance_date'], 'asset_maint_asset_date_idx');
            });

            return;
        }

        Schema::table('asset_maintenance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_maintenance_records', 'farm_asset_id')) {
                $table
                    ->foreignId('farm_asset_id')
                    ->nullable()
                    ->constrained('farm_assets')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'maintenance_date')) {
                $table->date('maintenance_date')->nullable();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'maintenance_type')) {
                $table->string('maintenance_type')->default('routine');
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'cost')) {
                $table->decimal('cost', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'performed_by')) {
                $table->string('performed_by')->nullable();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'next_service_date')) {
                $table->date('next_service_date')->nullable();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'created_by')) {
                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('asset_maintenance_records', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_records');
    }
};
