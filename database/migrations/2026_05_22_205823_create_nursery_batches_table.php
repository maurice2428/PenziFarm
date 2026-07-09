<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('nursery_batches')) {
            Schema::create('nursery_batches', function (Blueprint $table) {
                $table->id();

                $table->string('batch_code')->unique();
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

                $table->date('sowing_date');

                $table->decimal('seed_quantity', 12, 3)->default(0);
                $table->string('seed_unit')->nullable();

                $table->date('expected_germination_from')->nullable();
                $table->date('expected_germination_to')->nullable();
                $table->date('actual_germination_date')->nullable();

                $table->date('expected_transplant_date')->nullable();

                $table->unsignedInteger('initial_seedlings')->default(0);
                $table->unsignedInteger('germinated_seedlings')->default(0);
                $table->unsignedInteger('healthy_seedlings')->default(0);
                $table->unsignedInteger('weak_seedlings')->default(0);
                $table->unsignedInteger('dead_seedlings')->default(0);
                $table->unsignedInteger('transplanted_seedlings')->default(0);

                $table->decimal('germination_percent', 5, 2)->nullable();

                $table->string('growth_stage')->default('sown');
                // sown, germinating, emerged, hardening, ready_to_transplant, transplanted, closed

                $table->string('status')->default('active');
                // active, ready, transplanted, failed, closed

                $table->decimal('total_input_cost', 15, 2)->default(0);

                $table->text('notes')->nullable();

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['crop_catalog_id', 'status'], 'nursery_crop_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_batches');
    }
};
