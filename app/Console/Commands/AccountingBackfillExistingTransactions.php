<?php

namespace App\Console\Commands;

use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingBackfillExistingTransactions extends Command
{
    protected $signature = 'accounting:post-existing
        {--table=* : Optional specific table(s) to backfill}
        {--limit=0 : Limit rows per table. 0 means no limit}';

    protected $description = 'Post existing sales, payments, purchases, project expenses and farm costs into accounting journals without duplicating already-posted records.';

    public function handle(AccountingIntegrationPostingService $postingService): int
    {
        $tables = $this->option('table') ?: $this->supportedTables();
        $limit = (int) $this->option('limit');

        foreach ($tables as $table) {
            if (! in_array($table, $this->supportedTables(), true)) {
                $this->warn("Skipped unsupported table: {$table}");
                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->warn("Missing table: {$table}");
                continue;
            }

            $query = DB::table($table)->orderBy('id');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $rows = $query->get();
            $posted = 0;

            $this->line("Backfilling {$table}: {$rows->count()} row(s)");

            foreach ($rows as $row) {
                $model = new class extends Model {
                    public $timestamps = false;
                    protected $guarded = [];
                };

                $model->setTable($table);
                $model->exists = true;
                $model->setRawAttributes((array) $row, true);

                $journal = $postingService->postModel($model, 'backfill');

                if ($journal) {
                    $posted++;
                }
            }

            $this->info("Posted {$posted} accounting journal(s) from {$table}.");
        }

        $this->newLine();
        $this->info('Backfill complete. Existing duplicate-safe journal checks were applied.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function supportedTables(): array
    {
        return [
            'sales_invoices',
            'sales_payments',
            'purchase_orders',
            'purchase_order_receipts',
            'purchase_order_payments',
            'project_expenses',
            'animal_feedings',
            'animal_health_records',
        ];
    }
}
