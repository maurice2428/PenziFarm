<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casual_payroll_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('casual_payroll_id')
                ->constrained('casual_payrolls')
                ->cascadeOnDelete();

            $table->string('casual_name');
            $table->string('id_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('designation')->nullable();
            $table->string('work_site')->nullable();

            $table->decimal('saturday_amount', 12, 2)->default(0);
            $table->decimal('sunday_amount', 12, 2)->default(0);
            $table->decimal('monday_amount', 12, 2)->default(0);
            $table->decimal('tuesday_amount', 12, 2)->default(0);
            $table->decimal('wednesday_amount', 12, 2)->default(0);
            $table->decimal('thursday_amount', 12, 2)->default(0);
            $table->decimal('friday_amount', 12, 2)->default(0);

            $table->decimal('days_worked', 8, 2)->default(0);
            $table->decimal('total_pay', 15, 2)->default(0);

            $table->string('signature')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('casual_name');
            $table->index('id_number');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casual_payroll_items');
    }
};
