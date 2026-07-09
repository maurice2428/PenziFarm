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

            if (! Schema::hasColumn('mpesa_transactions', 'sales_invoice_id')) {
                $table->foreignId('sales_invoice_id')
                    ->nullable()
                    ->after('sales_payment_id')
                    ->constrained('sales_invoices')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mpesa_transactions', 'customer_id')) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->after('sales_invoice_id')
                    ->constrained('customers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mpesa_transactions', 'merchant_request_id')) {
                $table->string('merchant_request_id')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'checkout_request_id')) {
                $table->string('checkout_request_id')->nullable()->unique()->after('merchant_request_id');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('checkout_request_id');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0)->after('phone_number');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'account_reference')) {
                $table->string('account_reference')->nullable()->after('amount');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'transaction_desc')) {
                $table->string('transaction_desc')->nullable()->after('account_reference');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('transaction_desc');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'result_code')) {
                $table->string('result_code')->nullable()->after('mpesa_receipt_number');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'result_desc')) {
                $table->text('result_desc')->nullable()->after('result_code');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'status')) {
                $table->string('status')->default('pending')->after('result_desc');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'request_payload')) {
                $table->json('request_payload')->nullable()->after('status');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'callback_payload')) {
                $table->json('callback_payload')->nullable()->after('request_payload');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('callback_payload');
            }

            if (! Schema::hasColumn('mpesa_transactions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $columns = [
                'paid_at',
                'requested_at',
                'callback_payload',
                'request_payload',
                'status',
                'result_desc',
                'result_code',
                'mpesa_receipt_number',
                'transaction_desc',
                'account_reference',
                'amount',
                'phone_number',
                'checkout_request_id',
                'merchant_request_id',
                'customer_id',
                'sales_invoice_id',
                'sales_payment_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('mpesa_transactions', $column)) {
                    try {
                        $table->dropColumn($column);
                    } catch (\Throwable $e) {
                        //
                    }
                }
            }
        });
    }
};
