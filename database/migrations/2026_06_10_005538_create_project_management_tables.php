<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('type')->default('other')->index();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('farm_projects', function (Blueprint $table) {
            $table->id();

            $table->string('project_number')->unique();
            $table->foreignId('project_category_id')->nullable()->constrained('project_categories')->nullOnDelete();

            $table->string('name');
            $table->string('project_type')->default('other')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('planned')->index();

            $table->string('location')->nullable();
            $table->decimal('land_area', 12, 2)->nullable();
            $table->string('land_area_unit')->default('acres');

            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('scope_of_work')->nullable();

            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();

            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->decimal('approved_budget_amount', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);

            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->string('contractor_name')->nullable();
            $table->string('contractor_phone')->nullable();
            $table->string('contractor_email')->nullable();

            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_type', 'status']);
            $table->index(['start_date', 'expected_end_date']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status')->default('pending')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->date('target_date')->nullable();
            $table->date('completed_at')->nullable();

            $table->decimal('budget_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status')->default('pending')->index();
            $table->string('priority')->default('medium')->index();

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();

            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_budget_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();

            $table->string('cost_category')->default('other')->index();
            $table->string('item_name');
            $table->text('description')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);

            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);

            $table->unsignedBigInteger('supplier_id')->nullable();

            $table->string('status')->default('planned')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();
            $table->foreignId('project_budget_line_id')->nullable()->constrained('project_budget_lines')->nullOnDelete();

            $table->date('expense_date');
            $table->string('expense_type')->default('other')->index();

            $table->string('reference_no')->nullable()->index();
            $table->string('payee')->nullable();
            $table->string('payment_method')->default('cash')->index();

            $table->text('description')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);

            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->string('receipt_path')->nullable();

            $table->string('status')->default('pending')->index();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'status']);
        });

        Schema::create('project_progress_updates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();

            $table->date('update_date');
            $table->string('title');
            $table->text('narrative')->nullable();

            $table->unsignedTinyInteger('progress_percent')->default(0);

            $table->string('weather_condition')->nullable();
            $table->text('work_done')->nullable();
            $table->text('blockers')->nullable();
            $table->text('next_steps')->nullable();

            $table->string('photo_path')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_project_id')->constrained('farm_projects')->cascadeOnDelete();

            $table->string('title');
            $table->string('document_type')->default('other')->index();
            $table->string('file_path');
            $table->text('description')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_progress_updates');
        Schema::dropIfExists('project_expenses');
        Schema::dropIfExists('project_budget_lines');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('farm_projects');
        Schema::dropIfExists('project_categories');
    }
};
