<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('enable_bank_payment');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_branch');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('bank_swift_code')->nullable()->after('bank_account_number');
            $table->string('bank_paybill_number')->nullable()->after('bank_swift_code');
            $table->string('bank_account_reference')->nullable()->after('bank_paybill_number');
            $table->string('bank_logo')->nullable()->after('bank_account_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_branch',
                'bank_account_name',
                'bank_account_number',
                'bank_swift_code',
                'bank_paybill_number',
                'bank_account_reference',
                'bank_logo',
            ]);
        });
    }
};
