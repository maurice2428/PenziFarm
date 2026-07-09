<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('full_name')->index();
            $table->string('id_passport_number')->nullable()->unique();
            $table->string('kra_pin')->nullable()->unique();
            $table->string('nssf_number')->nullable()->unique();
            $table->string('nhif_sha_number')->nullable()->unique();
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('county')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_address')->nullable();
            $table->date('hire_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employment_type')->nullable();
            $table->string('work_station')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('avatar_path')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('house_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('tax_enabled')->default(true);
            $table->boolean('nssf_enabled')->default(true);
            $table->boolean('sha_enabled')->default(true);
            $table->boolean('housing_levy_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('exit_date')->nullable();
            $table->string('exit_reason')->nullable();
            $table->string('clearance_status')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
