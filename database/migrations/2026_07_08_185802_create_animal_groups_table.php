<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_code')->unique();
            $table->string('name');

            $table->string('group_type')->default('manual');
            $table->string('status')->default('active');

            $table->string('purpose')->nullable();
            $table->text('description')->nullable();

            $table->boolean('auto_sync')->default(false);

            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('breed_id')->nullable();

            $table->string('sex')->nullable();
            $table->string('animal_status')->nullable();
            $table->string('animal_purpose')->nullable();

            $table->json('criteria')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'group_type']);
            $table->index(['location_id', 'breed_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_groups');
    }
};
