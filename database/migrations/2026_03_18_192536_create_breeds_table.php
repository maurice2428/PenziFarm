<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->string('parent_category', 50);
            $table->string('breed_name');
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->index('parent_category');
            $table->unique(['parent_category', 'breed_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};
