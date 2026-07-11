<?php

namespace App\Console\Commands;

use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingJournalEntryLine;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingPostingFailure;
use App\Models\Accounting\AccountingTaxSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingDoctor extends Command
{
    protected $signature = 'accounting:doctor {--json : Print machine-readable JSON}';
    protected $description = 'Audit Accounting Core V2 configuration, balances, periods, mappings and posting failures.';

    public function handle(): int
    {
        $checks = [];
        $checks['tables'] = collect([
            'accounting_accounts','accounting_journal_entries','accounting_journal_entry_lines','accounting_source_postings','accounting_tax_transactions','accounting_posting_failures',
        ])->mapWithKeys(fn(string $table):array=>[$table=>Schema::hasTable($table)]);

        $checks['counts'] = [
            'accounts' => Schema::hasTable('accounting_accounts') ? DB::table('accounting_accounts')->count() : 0,
            'journals' => Schema::hasTable('accounting_journal_entries') ? DB::table('accounting_journal_entries')->count() : 0,
            'journal_lines' => Schema::hasTable('accounting_journal_entry_lines') ? DB::table('accounting_journal_entry_lines')->count() : 0,
            'draft_journals' => AccountingJournalEntry::query()->where('status','draft')->count(),
            'pending_failures' => Schema::hasTable('accounting_posting_failures') ? AccountingPostingFailure::query()->where('status','pending')->count() : 0,
        ];

        $checks['unbalanced_posted'] = AccountingJournalEntry::query()->where('status','posted')->whereRaw('ABS(total_debit - total_credit) >= 0.01')->count();
        $checks['orphan_lines'] = AccountingJournalEntryLine::query()->whereDoesntHave('journalEntry')->count();
        $checks['missing_required_mappings'] = AccountingAccountMapping::query()->where('is_required',true)->where('is_active',true)->where(function($q):void{$q->whereNull('account_id')->orWhereDoesntHave('account',fn($a)=>$a->where('is_active',true));})->pluck('key')->all();
        $checks['open_period_today'] = AccountingPeriod::query()->where('status','open')->whereDate('start_date','<=',now())->whereDate('end_date','>=',now())->exists();
        $checks['active_tax_rules'] = AccountingTaxSetting::query()->where('is_active',true)->pluck('code')->all();

        $healthy = $checks['tables']->every(fn(bool $exists):bool=>$exists)
            && $checks['unbalanced_posted'] === 0
            && $checks['orphan_lines'] === 0
            && $checks['missing_required_mappings'] === []
            && $checks['open_period_today'];

        $checks['healthy'] = $healthy;

        if ($this->option('json')) {
            $this->line(json_encode($checks, JSON_PRETTY_PRINT));
            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->components->twoColumnDetail('Overall status', $healthy ? '<fg=green>HEALTHY</>' : '<fg=red>ATTENTION REQUIRED</>');
        foreach ($checks['tables'] as $table=>$exists) $this->components->twoColumnDetail($table, $exists ? '<fg=green>OK</>' : '<fg=red>MISSING</>');
        foreach ($checks['counts'] as $label=>$count) $this->components->twoColumnDetail(str($label)->replace('_',' ')->title(), (string)$count);
        $this->components->twoColumnDetail('Unbalanced posted journals', (string)$checks['unbalanced_posted']);
        $this->components->twoColumnDetail('Orphan journal lines', (string)$checks['orphan_lines']);
        $this->components->twoColumnDetail('Open period covering today', $checks['open_period_today'] ? 'YES' : 'NO');
        $this->components->twoColumnDetail('Missing mappings', implode(', ', $checks['missing_required_mappings']) ?: 'None');
        $this->components->twoColumnDetail('Active tax rules', implode(', ', $checks['active_tax_rules']) ?: 'None');

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
