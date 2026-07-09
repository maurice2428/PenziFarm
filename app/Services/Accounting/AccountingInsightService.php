<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingJournalEntryLine;
use App\Models\Accounting\AccountingProjectFund;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingInsightService
{
    public function __construct(
        protected AccountingReportService $reports
    ) {
    }

    public function dashboard(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->toDateString() : now()->subMonths(5)->startOfMonth()->toDateString();
        $toDate = $to ? Carbon::parse($to)->toDateString() : now()->toDateString();

        $pl = $this->reports->profitAndLoss($fromDate, $toDate);
        $bs = $this->reports->balanceSheet($toDate);
        $tb = $this->reports->trialBalance($fromDate, $toDate);

        $income = (float) ($pl['income'] ?? 0);
        $expenses = (float) (($pl['cost_of_sales'] ?? 0) + ($pl['expenses'] ?? 0));
        $netProfit = (float) ($pl['net_profit'] ?? 0);
        $assets = (float) ($bs['assets'] ?? 0);
        $liabilities = (float) ($bs['liabilities'] ?? 0);
        $equity = (float) (($bs['equity'] ?? 0) + ($bs['current_year_profit'] ?? 0));
        $difference = (float) ($bs['difference'] ?? 0);

        $grossMargin = $income > 0 ? round(((float) ($pl['gross_profit'] ?? 0) / $income) * 100, 2) : 0.0;
        $netMargin = $income > 0 ? round(($netProfit / $income) * 100, 2) : 0.0;
        $debtRatio = $assets > 0 ? round(($liabilities / $assets) * 100, 2) : 0.0;

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'cards' => [
                ['label' => 'Income', 'value' => $income, 'hint' => 'Posted income in selected period', 'tone' => 'emerald'],
                ['label' => 'Expenses', 'value' => $expenses, 'hint' => 'Cost of sales + operating expenses', 'tone' => 'rose'],
                ['label' => 'Net Profit', 'value' => $netProfit, 'hint' => 'Income less farm costs', 'tone' => $netProfit >= 0 ? 'emerald' : 'rose'],
                ['label' => 'Assets', 'value' => $assets, 'hint' => 'Balance sheet assets as at selected date', 'tone' => 'sky'],
            ],
            'ratios' => [
                ['label' => 'Gross Margin', 'value' => $grossMargin, 'suffix' => '%', 'tone' => 'emerald'],
                ['label' => 'Net Margin', 'value' => $netMargin, 'suffix' => '%', 'tone' => $netMargin >= 0 ? 'emerald' : 'rose'],
                ['label' => 'Debt Ratio', 'value' => $debtRatio, 'suffix' => '%', 'tone' => 'amber'],
                ['label' => 'Balance Diff.', 'value' => $difference, 'suffix' => '', 'tone' => abs($difference) < 0.01 ? 'emerald' : 'rose'],
            ],
            'monthly' => $this->monthlyTrend($fromDate, $toDate),
            'account_mix' => $this->accountMix($tb),
            'project_funds' => $this->projectFundSnapshot(),
            'recent_journals' => $this->recentJournals(),
        ];
    }

    public function monthlyTrend(string $from, string $to): Collection
    {
        if (! Schema::hasTable('accounting_journal_entry_lines') || ! Schema::hasTable('accounting_journal_entries')) {
            return collect();
        }

        $start = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->endOfMonth();
        $months = collect();
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $months->put($cursor->format('Y-m'), [
                'label' => $cursor->format('M Y'),
                'income' => 0.0,
                'expense' => 0.0,
                'profit' => 0.0,
            ]);
            $cursor->addMonth();
        }

        $rows = AccountingJournalEntryLine::query()
            ->join('accounting_journal_entries as je', 'je.id', '=', 'accounting_journal_entry_lines.journal_entry_id')
            ->join('accounting_accounts as aa', 'aa.id', '=', 'accounting_journal_entry_lines.account_id')
            ->where('je.status', 'posted')
            ->whereDate('je.transaction_date', '>=', $from)
            ->whereDate('je.transaction_date', '<=', $to)
            ->whereIn('aa.type', ['income', 'cost_of_sales', 'expense'])
            ->selectRaw("DATE_FORMAT(je.transaction_date, '%Y-%m') as month_key")
            ->selectRaw("aa.type as account_type")
            ->selectRaw('SUM(accounting_journal_entry_lines.debit) as debits')
            ->selectRaw('SUM(accounting_journal_entry_lines.credit) as credits')
            ->groupBy('month_key', 'account_type')
            ->get();

        foreach ($rows as $row) {
            $item = $months->get($row->month_key);
            if (! $item) {
                continue;
            }

            $amount = in_array($row->account_type, ['income'], true)
                ? ((float) $row->credits - (float) $row->debits)
                : ((float) $row->debits - (float) $row->credits);

            if ($row->account_type === 'income') {
                $item['income'] += round($amount, 2);
            } else {
                $item['expense'] += round($amount, 2);
            }

            $item['profit'] = round($item['income'] - $item['expense'], 2);
            $months->put($row->month_key, $item);
        }

        return $months->values();
    }

    public function accountMix(Collection $trialBalance): Collection
    {
        $labels = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'cost_of_sales' => 'Cost of Sales',
            'expense' => 'Expenses',
        ];

        return collect($labels)->map(function (string $label, string $type) use ($trialBalance) {
            return [
                'label' => $label,
                'type' => $type,
                'amount' => round((float) $trialBalance->where('type', $type)->sum('balance'), 2),
            ];
        })->values();
    }

    public function projectFundSnapshot(): Collection
    {
        if (! Schema::hasTable('accounting_project_funds')) {
            return collect();
        }

        return AccountingProjectFund::query()
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (AccountingProjectFund $fund) => [
                'name' => $fund->name,
                'code' => $fund->fund_code,
                'type' => $fund->project_type,
                'status' => $fund->status,
                'budget' => (float) $fund->budget_amount,
                'received' => (float) $fund->received_amount,
                'spent' => (float) $fund->spent_amount,
                'balance' => (float) $fund->balance_amount,
                'utilization' => (float) $fund->utilization_percent,
            ]);
    }

    public function recentJournals(): Collection
    {
        if (! Schema::hasTable('accounting_journal_entries')) {
            return collect();
        }

        return DB::table('accounting_journal_entries')
            ->select('journal_number', 'transaction_date', 'status', 'source_type', 'reference', 'total_debit', 'total_credit', 'narration')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }
}
