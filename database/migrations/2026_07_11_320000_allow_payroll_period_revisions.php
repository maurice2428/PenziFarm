<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn(
            'payrolls',
            'revision'
        )) {
            Schema::table('payrolls', function (
                Blueprint $table
            ): void {
                $table->unsignedSmallInteger(
                    'revision'
                )
                    ->default(1)
                    ->after('year');
            });
        }

        DB::table('payrolls')
            ->whereNull('revision')
            ->update(['revision' => 1]);

        /*
         * The old unique index treats soft-deleted payrolls as active
         * because deleted_at is not part of the key.
         */
        if (
            $this->indexExists(
                'payrolls',
                'payrolls_month_year_unique'
            )
        ) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'DROP INDEX `payrolls_month_year_unique`'
            );
        }

        if (
            ! $this->indexExists(
                'payrolls',
                'payrolls_month_year_revision_unique'
            )
        ) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'ADD UNIQUE INDEX '
                . '`payrolls_month_year_revision_unique` '
                . '(`month`, `year`, `revision`)'
            );
        }

        /*
         * MySQL/MariaDB allows multiple NULL values in a unique index.
         * Archived payrolls generate NULL; an active payroll generates
         * "MM-YYYY". This enforces one active payroll per month while
         * allowing unlimited archived revisions.
         */
        if (! Schema::hasColumn(
            'payrolls',
            'active_period_key'
        )) {
            DB::statement(
                "ALTER TABLE `payrolls` "
                . "ADD COLUMN `active_period_key` VARCHAR(20) "
                . "GENERATED ALWAYS AS ("
                . "CASE "
                . "WHEN `deleted_at` IS NULL "
                . "THEN CONCAT("
                . "LPAD(`month`, 2, '0'), '-', `year`"
                . ") "
                . "ELSE NULL "
                . "END"
                . ") STORED"
            );
        }

        if (
            ! $this->indexExists(
                'payrolls',
                'payrolls_active_period_unique'
            )
        ) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'ADD UNIQUE INDEX '
                . '`payrolls_active_period_unique` '
                . '(`active_period_key`)'
            );
        }
    }

    public function down(): void
    {
        if (
            $this->indexExists(
                'payrolls',
                'payrolls_active_period_unique'
            )
        ) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'DROP INDEX '
                . '`payrolls_active_period_unique`'
            );
        }

        if (Schema::hasColumn(
            'payrolls',
            'active_period_key'
        )) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'DROP COLUMN `active_period_key`'
            );
        }

        if (
            $this->indexExists(
                'payrolls',
                'payrolls_month_year_revision_unique'
            )
        ) {
            DB::statement(
                'ALTER TABLE `payrolls` '
                . 'DROP INDEX '
                . '`payrolls_month_year_revision_unique`'
            );
        }

        if (Schema::hasColumn(
            'payrolls',
            'revision'
        )) {
            Schema::table('payrolls', function (
                Blueprint $table
            ): void {
                $table->dropColumn('revision');
            });
        }
    }

    private function indexExists(
        string $table,
        string $index
    ): bool {
        return DB::table(
            'information_schema.statistics'
        )
            ->whereRaw(
                'table_schema = DATABASE()'
            )
            ->where(
                'table_name',
                $table
            )
            ->where(
                'index_name',
                $index
            )
            ->exists();
    }
};
