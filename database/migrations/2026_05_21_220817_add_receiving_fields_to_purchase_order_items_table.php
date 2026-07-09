<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_items')) {
            return;
        }

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'received_quantity')) {
                $table->decimal('received_quantity', 15, 3)->default(0);
            }

            if (! Schema::hasColumn('purchase_order_items', 'rejected_quantity')) {
                $table->decimal('rejected_quantity', 15, 3)->default(0);
            }

            if (! Schema::hasColumn('purchase_order_items', 'receiving_status')) {
                $table->string('receiving_status')->default('pending');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_order_items')) {
            return;
        }

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'receiving_status')) {
                $table->dropColumn('receiving_status');
            }

            if (Schema::hasColumn('purchase_order_items', 'rejected_quantity')) {
                $table->dropColumn('rejected_quantity');
            }

            if (Schema::hasColumn('purchase_order_items', 'received_quantity')) {
                $table->dropColumn('received_quantity');
            }
        });
    }
};
