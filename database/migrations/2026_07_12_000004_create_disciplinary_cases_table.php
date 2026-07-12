<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 40)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('category', 80);
            $table->string('severity', 30)->default('minor');
            $table->text('allegation');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('investigation_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('show_cause_issued_at')->nullable();
            $table->text('employee_response')->nullable();
            $table->timestamp('hearing_date')->nullable();
            $table->text('hearing_notes')->nullable();
            $table->text('findings')->nullable();
            $table->string('sanction', 60)->nullable();
            $table->date('decision_date')->nullable();
            $table->date('suspension_start_date')->nullable();
            $table->date('suspension_end_date')->nullable();
            $table->string('appeal_status', 30)->nullable();
            $table->text('appeal_notes')->nullable();
            $table->string('status', 30)->default('open');
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['incident_date', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_cases');
    }
};
