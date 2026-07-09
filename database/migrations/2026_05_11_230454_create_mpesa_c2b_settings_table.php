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
        Schema::create('mpesa_c2b_settings', function (Blueprint $table) {
            $table->id();
            $table->string('short_code')->nullable();
            $table->string('environment')->default('sandbox');  // sandbox / production
            $table->string('validation_url')->nullable();
            $table->string('confirmation_url')->nullable();
            $table->string('response_type')->default('Completed');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b_settings');
    }
};
