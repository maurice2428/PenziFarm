<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('health_administrations')) {
            return;
        }

        Schema::create('health_administrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('health_product_id')
                ->constrained('health_products')
                ->restrictOnDelete();

            $table->date('administered_at');

            $table->integer('animal_count')->default(0);
            $table->decimal('dosage_per_animal', 14, 2)->default(0);
            $table->decimal('total_quantity_used', 14, 2)->default(0);

            $table->date('next_due_date')->nullable();

            $table->string('administered_by')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['health_product_id', 'administered_at']);
            $table->index(['next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_administrations');
    }
};
