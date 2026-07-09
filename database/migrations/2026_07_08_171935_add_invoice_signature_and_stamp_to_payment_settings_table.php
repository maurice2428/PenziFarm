<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_settings', 'invoice_signature_path')) {
                $table->string('invoice_signature_path')->nullable()->after('bank_logo');
            }

            if (! Schema::hasColumn('payment_settings', 'invoice_stamp_path')) {
                $table->string('invoice_stamp_path')->nullable()->after('invoice_signature_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            if (Schema::hasColumn('payment_settings', 'invoice_signature_path')) {
                $table->dropColumn('invoice_signature_path');
            }

            if (Schema::hasColumn('payment_settings', 'invoice_stamp_path')) {
                $table->dropColumn('invoice_stamp_path');
            }
        });
    }
};
