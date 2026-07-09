<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();

            $table->string('name');
            $table->string('type');  // vaccine, dewormer, dip, treatment
            $table->string('species')->nullable();

            $table->decimal('dosage_per_animal', 14, 2)->default(0);
            $table->string('dosage_unit')->default('ml');
            $table->string('administration_method')->nullable();

            $table->string('frequency')->nullable();  // once, monthly, quarterly, semi_annually, annually, custom
            $table->integer('frequency_days')->nullable();

            $table->string('batch_number')->nullable();
            $table->string('status')->default('active');

            $table->text('description')->nullable();
            $table->text('precautions')->nullable();
            $table->string('reference_document')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_products');
    }
};
