<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();

            $table->string('tag_number')->unique();
            $table->unsignedInteger('tag_sequence');

            $table->string('species', 50); // Sheep, Goat, Cattle, Poultry
            $table->foreignId('breed_id')->constrained('breeds')->restrictOnDelete();

            $table->enum('sex', ['Male', 'Female']);
            $table->date('date_of_birth')->nullable();
            $table->boolean('date_of_birth_is_estimated')->default(false);

            $table->enum('source', ['Born on farm', 'Purchased'])->default('Born on farm');
            $table->string('source_reference_type')->nullable();
            $table->unsignedBigInteger('source_reference_id')->nullable();

            $table->enum('status', ['Active', 'Sold', 'Dead', 'Culled'])->default('Active');

            $table->enum('purpose', ['Breeding', 'Sale', 'Dairy', 'Production'])->default('Sale');
            $table->boolean('is_breeder')->default(false);
            $table->boolean('sale_ready')->default(false);

            $table->decimal('valuation_price', 15, 2)->nullable();

            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();

            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['breed_id', 'tag_sequence']);
            $table->index(['status']);
            $table->index(['purpose']);
            $table->index(['is_breeder']);
            $table->index(['sale_ready']);
            $table->index(['species']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
