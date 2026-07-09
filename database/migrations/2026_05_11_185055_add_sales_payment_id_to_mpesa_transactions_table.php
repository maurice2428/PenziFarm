<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('mpesa_transactions', 'sales_payment_id')) {
                $table->foreignId('sales_payment_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('sales_payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('mpesa_transactions', 'sales_payment_id')) {
                $table->dropConstrainedForeignId('sales_payment_id');
            }
        });
    }
};
