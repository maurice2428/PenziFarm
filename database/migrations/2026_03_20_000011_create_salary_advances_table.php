<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('request_date');
            $table->decimal('amount_requested', 12, 2);
            $table->decimal('amount_approved', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('repayment_mode')->nullable();
            $table->integer('repayment_months')->default(1);
            $table->decimal('monthly_deduction', 12, 2)->default(0);
            $table->string('approval_status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
