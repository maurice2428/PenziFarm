<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_c2_b_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'trans_id')) {
                $table->string('trans_id')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'trans_amount')) {
                $table->decimal('trans_amount', 15, 2)->default(0)->after('trans_id');
            }

            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'bill_ref_number')) {
                $table->string('bill_ref_number')->nullable()->after('trans_amount');
            }

            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('bill_ref_number');
            }

            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'payload')) {
                $table->json('payload')->nullable()->after('phone_number');
            }

            if (! Schema::hasColumn('mpesa_c2_b_transactions', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_c2_b_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'trans_id',
                'trans_amount',
                'bill_ref_number',
                'phone_number',
                'payload',
                'verified_at',
            ]);
        });
    }
};
