<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casual_payroll_items', function (Blueprint $table) {
            $table->decimal('daily_rate', 12, 2)->default(0)->after('work_site');
        });
    }

    public function down(): void
    {
        Schema::table('casual_payroll_items', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
