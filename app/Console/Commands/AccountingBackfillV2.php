<?php

namespace App\Console\Commands;

use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingBackfillV2 extends Command
{
    protected $signature = 'accounting:backfill-v2
        {--table=* : Specific supported table(s)}
        {--limit=0 : Maximum records per table}
        {--commit : Create journals; without this flag the command is a dry run}';

    protected $description = 'Audit or post existing source transactions into Accounting Core V2 without duplicate journals.';

    public function handle(AccountingIntegrationPostingService $posting): int
    {
        $tables = $this->option('table') ?: $this->supportedTables();
        $limit = max(0, (int)$this->option('limit'));
        $commit = (bool)$this->option('commit');
        $summary = [];

        foreach ($tables as $table) {
            if (! in_array($table,$this->supportedTables(),true)) { $this->warn("Unsupported table: {$table}"); continue; }
            if (! Schema::hasTable($table)) { $this->warn("Missing table: {$table}"); continue; }

            $query = DB::table($table)->orderBy('id');
            if ($limit > 0) $query->limit($limit);
            $rows = $query->get();
            $posted = 0; $skipped = 0;

            foreach ($rows as $row) {
                if (! $commit) { $skipped++; continue; }
                $model = new class extends Model { public $timestamps=false; protected $guarded=[]; };
                $model->setTable($table); $model->exists=true; $model->setRawAttributes((array)$row,true);
                $journal = $posting->postModel($model,'backfill-v2');
                $journal ? $posted++ : $skipped++;
            }

            $summary[] = [$table,$rows->count(),$posted,$skipped,$commit?'COMMIT':'DRY RUN'];
        }

        $this->table(['Table','Rows','Posted','Skipped / Audit','Mode'],$summary);
        if (! $commit) $this->warn('Dry run only. Re-run with --commit after reviewing account mappings and open periods.');
        return self::SUCCESS;
    }

    private function supportedTables(): array
    {
        return ['sales_invoices','sales_payments','purchase_order_receipts','purchase_order_payments','stock_movements','payrolls','project_expenses','animal_feedings','animal_health_records'];
    }
}
