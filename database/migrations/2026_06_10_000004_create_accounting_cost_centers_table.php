<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('accounting_cost_centers')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['department', 'project', 'crop', 'livestock', 'asset', 'admin', 'other'])->default('department');
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_cost_centers');
    }
};
