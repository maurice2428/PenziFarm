<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_project_funds', function (Blueprint $table) {
            $table->id();
            $table->string('fund_code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('funding_source_id')->nullable()->constrained('accounting_funding_sources')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('accounting_cost_centers')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('project_type', ['infrastructure', 'crop', 'livestock', 'asset', 'cctv', 'paddocking', 'water', 'road', 'admin', 'other'])->default('other');
            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->enum('status', ['planned', 'active', 'paused', 'completed', 'cancelled'])->default('planned')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_project_funds');
    }
};
