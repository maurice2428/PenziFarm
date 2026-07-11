<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_tax_settings')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE accounting_tax_settings MODIFY type "
                . "ENUM('vat','paye','nssf','shif','housing_levy',"
                . "'withholding','withholding_vat','corporation_tax',"
                . "'turnover_tax','other') NOT NULL DEFAULT 'other'"
            );
        }
    }

    public function down(): void
    {
        // Non-destructive: tax records may already use the added types.
    }
};
