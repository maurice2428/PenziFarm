<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('animal_tag_prefix_counters')) {
            return;
        }

        Schema::create('animal_tag_prefix_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('tag_prefix', 32)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_tag_prefix_counters');
    }
};
