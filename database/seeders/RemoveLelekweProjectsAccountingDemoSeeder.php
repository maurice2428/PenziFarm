<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes ONLY the reversible DEMO-prefixed records created by
 * LelekweProjectsAccountingDemoSeeder. It does not truncate normal tables.
 */
class RemoveLelekweProjectsAccountingDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $projectIds = $this->idsWhereLike('farm_projects', 'project_number', 'DEMO-%');
            $projectFundIds = $this->idsWhereLike('accounting_project_funds', 'fund_code', 'DEMO-%');
            $journalIds = $this->idsWhereLike('accounting_journal_entries', 'journal_number', 'DEMO-%');
            $reconciliationIds = $this->idsWhereLike('accounting_reconciliations', 'reconciliation_number', 'DEMO-%');
            $fiscalYearIds = $this->idsWhereLike('accounting_fiscal_years', 'name', 'DEMO FY %');

            $this->deleteProjectChildren($projectIds);

            $this->deleteWhereIn('accounting_reconciliation_lines', 'reconciliation_id', $reconciliationIds);
            $this->deleteWhereIn('accounting_reconciliations', 'id', $reconciliationIds);
            $this->deleteWhereIn('accounting_project_fund_transactions', 'project_fund_id', $projectFundIds);
            $this->deleteWhereIn('accounting_journal_entry_lines', 'journal_entry_id', $journalIds);
            $this->deleteWhereIn('accounting_journal_entries', 'id', $journalIds);
            $this->deleteWhereIn('accounting_periods', 'fiscal_year_id', $fiscalYearIds);

            if (Schema::hasTable('accounting_project_funds')) {
                DB::table('accounting_project_funds')->where('fund_code', 'like', 'DEMO-%')->delete();
            }

            if (Schema::hasTable('accounting_funding_sources')) {
                DB::table('accounting_funding_sources')->where('name', 'like', 'DEMO — %')->delete();
            }

            if (Schema::hasTable('accounting_account_mappings')) {
                DB::table('accounting_account_mappings')->where('key', 'like', 'demo_%')->delete();
            }

            if (Schema::hasTable('accounting_tax_settings')) {
                DB::table('accounting_tax_settings')->where('code', 'like', 'DEMO-%')->delete();
            }

            $this->deleteWhereIn('accounting_fiscal_years', 'id', $fiscalYearIds);

            if (Schema::hasTable('accounting_cost_centers')) {
                DB::table('accounting_cost_centers')->where('code', 'like', 'DEMO-%')->delete();
            }

            if (Schema::hasTable('accounting_accounts')) {
                DB::table('accounting_accounts')->where('code', 'like', 'DEMO-%')->delete();
            }

            if (Schema::hasTable('project_categories')) {
                DB::table('project_categories')->where('code', 'like', 'DEMO-%')->delete();
            }
        });

        $this->command?->info('DEMO Projects & Accounting records removed.');
    }

    /** @param array<int,int> $projectIds */
    private function deleteProjectChildren(array $projectIds): void
    {
        if ($projectIds === []) {
            return;
        }

        foreach ([
            'project_milestones',
            'project_tasks',
            'project_budget_lines',
            'project_expenses',
            'project_progress_updates',
            'project_documents',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (['farm_project_id', 'project_id'] as $foreignKey) {
                if (Schema::hasColumn($table, $foreignKey)) {
                    DB::table($table)->whereIn($foreignKey, $projectIds)->delete();
                    break;
                }
            }
        }

        if (Schema::hasTable('farm_projects')) {
            DB::table('farm_projects')->whereIn('id', $projectIds)->delete();
        }
    }

    /** @return array<int,int> */
    private function idsWhereLike(string $table, string $column, string $value): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->where($column, 'like', $value)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /** @param array<int,int> $ids */
    private function deleteWhereIn(string $table, string $column, array $ids): void
    {
        if ($ids === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $ids)->delete();
    }
}
