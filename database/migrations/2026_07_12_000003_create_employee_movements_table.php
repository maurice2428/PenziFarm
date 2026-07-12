<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('movement_type', 40);
            $table->date('effective_date');
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('from_job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('to_job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->decimal('from_basic_salary', 14, 2)->nullable();
            $table->decimal('to_basic_salary', 14, 2)->nullable();
            $table->string('previous_status', 40)->nullable();
            $table->string('new_status', 40)->nullable();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->string('approval_status', 30)->default('approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
            $table->index(['movement_type', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_movements');
    }
};
