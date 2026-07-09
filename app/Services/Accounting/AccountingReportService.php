<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingJournalEntryLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    public function trialBalance(?string $from = null, ?string $to = null): Collection
    {
        $fromDate = $from ? Carbon::parse($from)->toDateString() : null;
        $toDate = $to ? Carbon::parse($to)->toDateString() : now()->toDateString();

        $lines = AccountingJournalEntryLine::query()
            ->select('account_id', DB::raw('SUM(debit) as debits'), DB::raw('SUM(credit) as credits'))
            ->whereHas('journalEntry', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'posted')
                    ->when($fromDate, fn ($q) => $q->whereDate('transaction_date', '>=', $fromDate))
                    ->whereDate('transaction_date', '<=', $toDate);
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return AccountingAccount::query()
            ->active()
            ->orderBy('code')
            ->get()
            ->map(function (AccountingAccount $account) use ($lines) {
                $line = $lines->get($account->id);
                $debits = round((float) ($line->debits ?? 0), 2);
                $credits = round((float) ($line->credits ?? 0), 2);
                $balance = $account->signedBalance($debits, $credits);

                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'debits' => $debits,
                    'credits' => $credits,
                    'balance' => $balance,
                    'debit_balance' => $account->normal_balance === 'debit' ? max($balance, 0) : max(-$balance, 0),
                    'credit_balance' => $account->normal_balance === 'credit' ? max($balance, 0) : max(-$balance, 0),
                ];
            });
    }

    public function generalLedger(int $accountId, ?string $from = null, ?string $to = null): Collection
    {
        $fromDate = $from ? Carbon::parse($from)->toDateString() : null;
        $toDate = $to ? Carbon::parse($to)->toDateString() : now()->toDateString();
        $account = AccountingAccount::findOrFail($accountId);
        $runningBalance = 0.0;

        return AccountingJournalEntryLine::query()
            ->with(['journalEntry', 'costCenter', 'projectFund'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($fromDate, $toDate) {
                $query->where('status', 'posted')
                    ->when($fromDate, fn ($q) => $q->whereDate('transaction_date', '>=', $fromDate))
                    ->whereDate('transaction_date', '<=', $toDate);
            })
            ->join('accounting_journal_entries', 'accounting_journal_entries.id', '=', 'accounting_journal_entry_lines.journal_entry_id')
            ->orderBy('accounting_journal_entries.transaction_date')
            ->orderBy('accounting_journal_entry_lines.id')
            ->select('accounting_journal_entry_lines.*')
            ->get()
            ->map(function (AccountingJournalEntryLine $line) use (&$runningBalance, $account) {
                $delta = $account->normal_balance === 'debit'
                    ? ((float) $line->debit - (float) $line->credit)
                    : ((float) $line->credit - (float) $line->debit);

                $runningBalance = round($runningBalance + $delta, 2);

                return [
                    'date' => $line->journalEntry->transaction_date?->format('Y-m-d'),
                    'journal_number' => $line->journalEntry->journal_number,
                    'reference' => $line->journalEntry->reference,
                    'description' => $line->description ?: $line->journalEntry->narration,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'balance' => $runningBalance,
                    'cost_center' => $line->costCenter?->name,
                    'project' => $line->projectFund?->name,
                ];
            });
    }

    public function profitAndLoss(?string $from = null, ?string $to = null): array
    {
        $tb = $this->trialBalance($from, $to);

        $income = $tb->where('type', 'income')->sum('balance');
        $costOfSales = $tb->where('type', 'cost_of_sales')->sum('balance');
        $expenses = $tb->where('type', 'expense')->sum('balance');

        return [
            'income' => round($income, 2),
            'cost_of_sales' => round($costOfSales, 2),
            'gross_profit' => round($income - $costOfSales, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => round($income - $costOfSales - $expenses, 2),
            'lines' => $tb->whereIn('type', ['income', 'cost_of_sales', 'expense'])->values(),
        ];
    }

    public function balanceSheet(?string $asAt = null): array
    {
        $tb = $this->trialBalance(null, $asAt);
        $assets = $tb->where('type', 'asset')->sum('balance');
        $liabilities = $tb->where('type', 'liability')->sum('balance');
        $equity = $tb->where('type', 'equity')->sum('balance');
        $profit = $this->profitAndLoss(null, $asAt)['net_profit'];

        return [
            'assets' => round($assets, 2),
            'liabilities' => round($liabilities, 2),
            'equity' => round($equity, 2),
            'current_year_profit' => round($profit, 2),
            'liabilities_and_equity' => round($liabilities + $equity + $profit, 2),
            'difference' => round($assets - ($liabilities + $equity + $profit), 2),
            'lines' => $tb->whereIn('type', ['asset', 'liability', 'equity'])->values(),
        ];
    }
}
