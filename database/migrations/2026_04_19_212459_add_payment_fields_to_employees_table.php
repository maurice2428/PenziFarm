<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('other_allowance');
            $table->string('mpesa_number')->nullable()->after('account_number');
            $table->string('airtel_money_number')->nullable()->after('mpesa_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'mpesa_number',
                'airtel_money_number',
            ]);
        });
    }
};
