<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casual_payrolls', function (Blueprint $table) {
            $table->id();

            $table->string('farm_name')->nullable();
            $table->string('title')->nullable();

            $table->date('week_start')->nullable();
            $table->date('week_end')->nullable();

            $table->string('work_site')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedInteger('total_casuals')->default(0);
            $table->decimal('total_days_worked', 12, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['week_start', 'week_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casual_payrolls');
    }
};
