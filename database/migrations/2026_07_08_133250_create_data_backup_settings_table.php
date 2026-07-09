<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_backup_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_enabled')->default(true);
            $table->time('run_time')->default('23:00:00');
            $table->string('timezone')->default('Africa/Nairobi');
            $table->unsignedInteger('keep_last')->default(14);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_backup_settings');
    }
};
