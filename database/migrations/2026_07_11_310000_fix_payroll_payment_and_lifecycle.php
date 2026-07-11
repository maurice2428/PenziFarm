<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (
            Blueprint $table
        ): void {
            if (! Schema::hasColumn(
                'payrolls',
                'cancelled_by'
            )) {
                $table->foreignId('cancelled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn(
                'payrolls',
                'cancelled_at'
            )) {
                $table->timestamp('cancelled_at')
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn(
                'payrolls',
                'cancellation_reason'
            )) {
                $table->text('cancellation_reason')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'payrolls',
                'deleted_at'
            )) {
                $table->softDeletes();
            }
        });

        /*
         * Existing installations may have the column without a default.
         * Normalize it so draft parent rows can always be inserted before
         * their employee payment lines are generated.
         */
        DB::table('payroll_payments')
            ->whereNull('total_amount')
            ->update(['total_amount' => 0]);

        Schema::table('payroll_payments', function (
            Blueprint $table
        ): void {
            $table->decimal(
                'total_amount',
                15,
                2
            )->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (
            Blueprint $table
        ): void {
            if (Schema::hasColumn(
                'payrolls',
                'cancelled_by'
            )) {
                $table->dropConstrainedForeignId(
                    'cancelled_by'
                );
            }

            $columns = [];

            foreach ([
                'cancelled_at',
                'cancellation_reason',
                'deleted_at',
            ] as $column) {
                if (Schema::hasColumn(
                    'payrolls',
                    $column
                )) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
