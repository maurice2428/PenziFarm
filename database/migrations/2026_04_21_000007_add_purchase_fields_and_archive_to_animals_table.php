<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (! Schema::hasColumn('animals', 'bought_on')) {
                $table->date('bought_on')->nullable()->after('source');
            }

            if (! Schema::hasColumn('animals', 'bought_from')) {
                $table->string('bought_from')->nullable()->after('bought_on');
            }

            if (! Schema::hasColumn('animals', 'seller_phone')) {
                $table->string('seller_phone', 50)->nullable()->after('bought_from');
            }

            if (! Schema::hasColumn('animals', 'seller_email')) {
                $table->string('seller_email')->nullable()->after('seller_phone');
            }

            if (! Schema::hasColumn('animals', 'seller_address')) {
                $table->text('seller_address')->nullable()->after('seller_email');
            }

            if (! Schema::hasColumn('animals', 'purchase_price')) {
                $table->decimal('purchase_price', 15, 2)->nullable()->after('seller_address');
            }

            if (! Schema::hasColumn('animals', 'purchase_notes')) {
                $table->text('purchase_notes')->nullable()->after('purchase_price');
            }

            if (! Schema::hasColumn('animals', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $columns = [
                'bought_on',
                'bought_from',
                'seller_phone',
                'seller_email',
                'seller_address',
                'purchase_price',
                'purchase_notes',
                'is_archived',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('animals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
