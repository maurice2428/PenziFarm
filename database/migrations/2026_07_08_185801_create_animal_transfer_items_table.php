<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('animal_transfer_id');
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();

            $table->string('tag_number')->nullable();
            $table->string('breed_name')->nullable();
            $table->string('sex')->nullable();

            $table->string('status')->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('animal_transfer_id', 'ati_transfer_id_fk')
                ->references('id')
                ->on('animal_transfers')
                ->cascadeOnDelete();

            $table->unique(['animal_transfer_id', 'animal_id'], 'animal_transfer_unique_animal');
            $table->index(['animal_id', 'status']);
            $table->index(['from_location_id', 'to_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_transfer_items');
    }
};
