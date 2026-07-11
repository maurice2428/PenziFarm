<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_payments')) {
            Schema::table('purchase_order_payments', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_order_payments', 'paid_at')) {
                    $table->dateTime('paid_at')->nullable()->after('payment_date')->index();
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_phone')) {
                    $table->string('mpesa_phone', 30)->nullable()->after('mpesa_reference');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_receipt_number')) {
                    $table->string('mpesa_receipt_number', 100)->nullable()->after('mpesa_phone')->index();
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_merchant_request_id')) {
                    $table->string('mpesa_merchant_request_id')->nullable()->after('mpesa_receipt_number');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_checkout_request_id')) {
                    $table->string('mpesa_checkout_request_id')->nullable()->after('mpesa_merchant_request_id');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_result_code')) {
                    $table->string('mpesa_result_code', 50)->nullable()->after('mpesa_checkout_request_id');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_result_description')) {
                    $table->text('mpesa_result_description')->nullable()->after('mpesa_result_code');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'mpesa_callback_payload')) {
                    $table->json('mpesa_callback_payload')->nullable()->after('mpesa_result_description');
                }

                if (! Schema::hasColumn('purchase_order_payments', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('notes')->index();
                }

                if (! Schema::hasColumn('purchase_order_payments', 'reversed_by')) {
                    $table->foreignId('reversed_by')
                        ->nullable()
                        ->after('reversed_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('purchase_order_payments', 'reversal_reason')) {
                    $table->text('reversal_reason')->nullable()->after('reversed_by');
                }
            });
        }

        if (Schema::hasTable('purchase_order_receipts')) {
            Schema::table('purchase_order_receipts', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_order_receipts', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('notes')->index();
                }

                if (! Schema::hasColumn('purchase_order_receipts', 'reversed_by')) {
                    $table->foreignId('reversed_by')
                        ->nullable()
                        ->after('reversed_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('purchase_order_receipts', 'reversal_reason')) {
                    $table->text('reversal_reason')->nullable()->after('reversed_by');
                }
            });
        }

        if (Schema::hasTable('purchase_order_receipt_items')) {
            Schema::table('purchase_order_receipt_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('purchase_order_receipt_items', 'rejection_disposition')) {
                    $table->string('rejection_disposition')->nullable()->after('rejection_reason')->index();
                }

                if (! Schema::hasColumn('purchase_order_receipt_items', 'rejection_status')) {
                    $table->string('rejection_status')->default('none')->after('rejection_disposition')->index();
                }

                if (! Schema::hasColumn('purchase_order_receipt_items', 'rejection_reference')) {
                    $table->string('rejection_reference')->nullable()->after('rejection_status');
                }

                if (! Schema::hasColumn('purchase_order_receipt_items', 'rejection_resolved_at')) {
                    $table->timestamp('rejection_resolved_at')->nullable()->after('rejection_reference');
                }

                if (! Schema::hasColumn('purchase_order_receipt_items', 'rejection_resolved_by')) {
                    $table->foreignId('rejection_resolved_by')
                        ->nullable()
                        ->after('rejection_resolved_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_order_receipt_items')) {
            Schema::table(
                'purchase_order_receipt_items',
                function (Blueprint $table): void {
                    if (
                        Schema::hasColumn(
                            'purchase_order_receipt_items',
                            'rejection_resolved_by'
                        )
                    ) {
                        $table->dropConstrainedForeignId(
                            'rejection_resolved_by'
                        );
                    }

                    foreach ([
                        'rejection_resolved_at',
                        'rejection_reference',
                        'rejection_status',
                        'rejection_disposition',
                    ] as $column) {
                        if (
                            Schema::hasColumn(
                                'purchase_order_receipt_items',
                                $column
                            )
                        ) {
                            $table->dropColumn($column);
                        }
                    }
                }
            );
        }

        if (Schema::hasTable('purchase_order_receipts')) {
            Schema::table(
                'purchase_order_receipts',
                function (Blueprint $table): void {
                    if (
                        Schema::hasColumn(
                            'purchase_order_receipts',
                            'reversed_by'
                        )
                    ) {
                        $table->dropConstrainedForeignId(
                            'reversed_by'
                        );
                    }

                    foreach (
                        ['reversal_reason', 'reversed_at']
                        as $column
                    ) {
                        if (
                            Schema::hasColumn(
                                'purchase_order_receipts',
                                $column
                            )
                        ) {
                            $table->dropColumn($column);
                        }
                    }
                }
            );
        }

        if (Schema::hasTable('purchase_order_payments')) {
            Schema::table(
                'purchase_order_payments',
                function (Blueprint $table): void {
                    if (
                        Schema::hasColumn(
                            'purchase_order_payments',
                            'reversed_by'
                        )
                    ) {
                        $table->dropConstrainedForeignId(
                            'reversed_by'
                        );
                    }

                    foreach ([
                        'reversal_reason',
                        'reversed_at',
                        'mpesa_callback_payload',
                        'mpesa_result_description',
                        'mpesa_result_code',
                        'mpesa_checkout_request_id',
                        'mpesa_merchant_request_id',
                        'mpesa_receipt_number',
                        'mpesa_phone',
                        'paid_at',
                    ] as $column) {
                        if (
                            Schema::hasColumn(
                                'purchase_order_payments',
                                $column
                            )
                        ) {
                            $table->dropColumn($column);
                        }
                    }
                }
            );
        }
    }
};
