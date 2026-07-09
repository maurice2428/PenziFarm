<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_group_members', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('animal_group_id');
            $table->unsignedBigInteger('animal_id');

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->string('status')->default('active');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('animal_group_id', 'agm_group_id_fk')
                ->references('id')
                ->on('animal_groups')
                ->cascadeOnDelete();

            $table->unique(['animal_group_id', 'animal_id'], 'animal_group_unique_member');
            $table->index(['animal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_group_members');
    }
};
