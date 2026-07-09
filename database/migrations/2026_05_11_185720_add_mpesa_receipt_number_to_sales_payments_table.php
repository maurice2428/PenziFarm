<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_payments', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')
                    ->nullable()
                    ->after('reference_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            if (Schema::hasColumn('sales_payments', 'mpesa_receipt_number')) {
                $table->dropColumn('mpesa_receipt_number');
            }
        });
    }
};
