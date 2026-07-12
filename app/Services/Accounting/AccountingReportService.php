<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingJournalEntryLine;
use App\Models\Accounting\AccountingTaxTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingReportService
{
    public function trialBalance(
        ?string $from = null,
        ?string $to = null
    ): Collection {
        $fromDate = $from
            ? Carbon::parse($from)->toDateString()
            : null;

        $toDate = $to
            ? Carbon::parse($to)->toDateString()
            : now('Africa/Nairobi')->toDateString();

        $lines = AccountingJournalEntryLine::query()
            ->select(
                'account_id',
                DB::raw('SUM(debit) as debits'),
                DB::raw('SUM(credit) as credits')
            )
            ->whereHas(
                'journalEntry',
                function ($query) use (
                    $fromDate,
                    $toDate
                ): void {
                    $query
                        ->where('status', 'posted')
                        ->when(
                            $fromDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'transaction_date',
                                    '>=',
                                    $fromDate
                                )
                        )
                        ->whereDate(
                            'transaction_date',
                            '<=',
                            $toDate
                        );
                }
            )
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        return AccountingAccount::query()
            ->active()
            ->orderBy('code')
            ->get()
            ->map(function (
                AccountingAccount $account
            ) use ($lines): array {
                $line = $lines->get(
                    $account->getKey()
                );

                $debits = round(
                    (float) ($line->debits ?? 0),
                    2
                );

                $credits = round(
                    (float) ($line->credits ?? 0),
                    2
                );

                $balance = $account->signedBalance(
                    $debits,
                    $credits
                );

                return [
                    'account_id' =>
                        $account->getKey(),

                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,

                    'normal_balance' =>
                        $account->normal_balance,

                    'debits' => $debits,
                    'credits' => $credits,
                    'balance' => $balance,

                    'debit_balance' =>
                        $account->normal_balance === 'debit'
                            ? max($balance, 0)
                            : max(-$balance, 0),

                    'credit_balance' =>
                        $account->normal_balance === 'credit'
                            ? max($balance, 0)
                            : max(-$balance, 0),
                ];
            })
            ->values();
    }

    public function generalLedger(
        int $accountId,
        ?string $from = null,
        ?string $to = null
    ): Collection {
        $fromDate = $from
            ? Carbon::parse($from)->toDateString()
            : null;

        $toDate = $to
            ? Carbon::parse($to)->toDateString()
            : now('Africa/Nairobi')->toDateString();

        $account = AccountingAccount::query()
            ->findOrFail($accountId);

        $runningBalance = 0.0;

        return AccountingJournalEntryLine::query()
            ->with([
                'journalEntry',
                'costCenter',
                'projectFund',
            ])
            ->where('account_id', $accountId)
            ->whereHas(
                'journalEntry',
                function ($query) use (
                    $fromDate,
                    $toDate
                ): void {
                    $query
                        ->where('status', 'posted')
                        ->when(
                            $fromDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'transaction_date',
                                    '>=',
                                    $fromDate
                                )
                        )
                        ->whereDate(
                            'transaction_date',
                            '<=',
                            $toDate
                        );
                }
            )
            ->join(
                'accounting_journal_entries',
                'accounting_journal_entries.id',
                '=',
                'accounting_journal_entry_lines.journal_entry_id'
            )
            ->orderBy(
                'accounting_journal_entries.transaction_date'
            )
            ->orderBy(
                'accounting_journal_entry_lines.id'
            )
            ->select(
                'accounting_journal_entry_lines.*'
            )
            ->get()
            ->map(function (
                AccountingJournalEntryLine $line
            ) use (
                &$runningBalance,
                $account
            ): array {
                $delta = $account->normal_balance === 'debit'
                    ? (
                        (float) $line->debit
                        - (float) $line->credit
                    )
                    : (
                        (float) $line->credit
                        - (float) $line->debit
                    );

                $runningBalance = round(
                    $runningBalance + $delta,
                    2
                );

                return [
                    'date' =>
                        $line->journalEntry
                            ?->transaction_date
                            ?->format('d M Y')
                        ?: '-',

                    'journal_number' =>
                        $line->journalEntry
                            ?->journal_number
                        ?: '-',

                    'reference' =>
                        $line->journalEntry
                            ?->reference,

                    'description' =>
                        $line->description
                        ?: $line->journalEntry
                            ?->narration,

                    'debit' =>
                        (float) $line->debit,

                    'credit' =>
                        (float) $line->credit,

                    'balance' =>
                        $runningBalance,

                    'cost_center' =>
                        $line->costCenter?->name,

                    'project' =>
                        $line->projectFund?->name,
                ];
            })
            ->values();
    }

    public function profitAndLoss(
        ?string $from = null,
        ?string $to = null
    ): array {
        $trialBalance = $this->trialBalance(
            $from,
            $to
        );

        $income = (float) $trialBalance
            ->where('type', 'income')
            ->sum('balance');

        $costOfSales = (float) $trialBalance
            ->where('type', 'cost_of_sales')
            ->sum('balance');

        $expenses = (float) $trialBalance
            ->where('type', 'expense')
            ->sum('balance');

        return [
            'income' => round($income, 2),

            'cost_of_sales' =>
                round($costOfSales, 2),

            'gross_profit' =>
                round(
                    $income - $costOfSales,
                    2
                ),

            'expenses' => round($expenses, 2),

            'net_profit' =>
                round(
                    $income
                    - $costOfSales
                    - $expenses,
                    2
                ),

            'lines' => $trialBalance
                ->whereIn(
                    'type',
                    [
                        'income',
                        'cost_of_sales',
                        'expense',
                    ]
                )
                ->values(),
        ];
    }

    public function balanceSheet(
        ?string $asAt = null
    ): array {
        $trialBalance = $this->trialBalance(
            null,
            $asAt
        );

        $assets = (float) $trialBalance
            ->where('type', 'asset')
            ->sum('balance');

        $liabilities = (float) $trialBalance
            ->where('type', 'liability')
            ->sum('balance');

        $equity = (float) $trialBalance
            ->where('type', 'equity')
            ->sum('balance');

        $profit = (float) $this->profitAndLoss(
            null,
            $asAt
        )['net_profit'];

        $liabilitiesAndEquity =
            $liabilities + $equity + $profit;

        return [
            'assets' => round($assets, 2),

            'liabilities' =>
                round($liabilities, 2),

            'equity' => round($equity, 2),

            'current_year_profit' =>
                round($profit, 2),

            'liabilities_and_equity' =>
                round(
                    $liabilitiesAndEquity,
                    2
                ),

            'difference' =>
                round(
                    $assets
                    - $liabilitiesAndEquity,
                    2
                ),

            'lines' => $trialBalance
                ->whereIn(
                    'type',
                    [
                        'asset',
                        'liability',
                        'equity',
                    ]
                )
                ->values(),
        ];
    }

    public function cashFlow(
        ?string $from = null,
        ?string $to = null
    ): array {
        $fromDate = $from
            ? Carbon::parse($from)->toDateString()
            : now('Africa/Nairobi')
                ->startOfYear()
                ->toDateString();

        $toDate = $to
            ? Carbon::parse($to)->toDateString()
            : now('Africa/Nairobi')->toDateString();

        $cashAccountIds =
            $this->cashEquivalentAccountIds();

        if ($cashAccountIds->isEmpty()) {
            return [
                'inflows' => 0.0,
                'outflows' => 0.0,
                'net_cash_flow' => 0.0,
                'movements' => 0,
                'lines' => collect(),
            ];
        }

        $lines = AccountingJournalEntryLine::query()
            ->with([
                'journalEntry',
                'account',
            ])
            ->whereIn(
                'account_id',
                $cashAccountIds
            )
            ->whereHas(
                'journalEntry',
                function ($query) use (
                    $fromDate,
                    $toDate
                ): void {
                    $query
                        ->where('status', 'posted')
                        ->whereDate(
                            'transaction_date',
                            '>=',
                            $fromDate
                        )
                        ->whereDate(
                            'transaction_date',
                            '<=',
                            $toDate
                        );
                }
            )
            ->join(
                'accounting_journal_entries',
                'accounting_journal_entries.id',
                '=',
                'accounting_journal_entry_lines.journal_entry_id'
            )
            ->orderBy(
                'accounting_journal_entries.transaction_date'
            )
            ->orderBy(
                'accounting_journal_entry_lines.id'
            )
            ->select(
                'accounting_journal_entry_lines.*'
            )
            ->get();

        $inflows = round(
            (float) $lines->sum('debit'),
            2
        );

        $outflows = round(
            (float) $lines->sum('credit'),
            2
        );

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,

            'net_cash_flow' =>
                round(
                    $inflows - $outflows,
                    2
                ),

            'movements' => $lines->count(),
            'lines' => $lines,
        ];
    }


    /**
     * Return tax transactions grouped for the Kenya Tax Compliance dashboard.
     *
     * The Blade view expects a Collection whose rows expose:
     * tax_code, direction, transaction_count, taxable_amount,
     * tax_amount and gross_amount.
     */
    public function taxSummary(
        ?string $from = null,
        ?string $to = null
    ): Collection {
        $fromDate = $from
            ? Carbon::parse($from)->toDateString()
            : now('Africa/Nairobi')
                ->startOfYear()
                ->toDateString();

        $toDate = $to
            ? Carbon::parse($to)->toDateString()
            : now('Africa/Nairobi')->toDateString();

        $model = new AccountingTaxTransaction();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return collect();
        }

        $columns = Schema::getColumnListing($table);

        $firstExistingColumn = static function (
            array $candidates
        ) use ($columns): ?string {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    return $candidate;
                }
            }

            return null;
        };

        $dateColumn = $firstExistingColumn([
            'transaction_date',
            'tax_date',
            'reporting_date',
            'period_date',
            'date',
            'created_at',
        ]);

        $taxCodeColumn = $firstExistingColumn([
            'tax_code',
            'code',
            'tax_type',
            'type',
            'category',
        ]);

        $directionColumn = $firstExistingColumn([
            'direction',
            'tax_direction',
            'entry_type',
        ]);

        $taxableAmountColumn = $firstExistingColumn([
            'taxable_amount',
            'net_amount',
            'base_amount',
            'amount_before_tax',
        ]);

        $taxAmountColumn = $firstExistingColumn([
            'tax_amount',
            'amount',
            'tax_value',
        ]);

        $grossAmountColumn = $firstExistingColumn([
            'gross_amount',
            'total_amount',
            'amount_inclusive_tax',
        ]);

        $statusColumn = $firstExistingColumn([
            'status',
            'filing_status',
            'payment_status',
        ]);

        if (! $taxAmountColumn) {
            return collect();
        }

        $query = AccountingTaxTransaction::query();

        if ($dateColumn) {
            $query
                ->whereDate(
                    $dateColumn,
                    '>=',
                    $fromDate
                )
                ->whereDate(
                    $dateColumn,
                    '<=',
                    $toDate
                );
        }

        if ($statusColumn) {
            $query->whereNotIn(
                $statusColumn,
                [
                    'reversed',
                    'cancelled',
                    'canceled',
                    'void',
                    'voided',
                ]
            );
        }

        $transactions = $query->get();

        return $transactions
            ->groupBy(
                function (
                    AccountingTaxTransaction $transaction
                ) use (
                    $taxCodeColumn,
                    $directionColumn
                ): string {
                    $taxCode = strtoupper(
                        trim(
                            (string) (
                                $taxCodeColumn
                                    ? data_get(
                                        $transaction,
                                        $taxCodeColumn
                                    )
                                    : 'UNSPECIFIED'
                            )
                        )
                    );

                    if ($taxCode === '') {
                        $taxCode = 'UNSPECIFIED';
                    }

                    $direction = strtolower(
                        trim(
                            (string) (
                                $directionColumn
                                    ? data_get(
                                        $transaction,
                                        $directionColumn
                                    )
                                    : 'output'
                            )
                        )
                    );

                    if ($direction === '') {
                        $direction = 'output';
                    }

                    return $taxCode . '|' . $direction;
                }
            )
            ->map(
                function (
                    Collection $group,
                    string $groupKey
                ) use (
                    $taxableAmountColumn,
                    $taxAmountColumn,
                    $grossAmountColumn
                ): object {
                    [$taxCode, $direction] = array_pad(
                        explode('|', $groupKey, 2),
                        2,
                        'output'
                    );

                    $taxableAmount = $taxableAmountColumn
                        ? (float) $group->sum(
                            fn (
                                AccountingTaxTransaction $transaction
                            ): float => (float) data_get(
                                $transaction,
                                $taxableAmountColumn,
                                0
                            )
                        )
                        : 0.0;

                    $taxAmount = (float) $group->sum(
                        fn (
                            AccountingTaxTransaction $transaction
                        ): float => (float) data_get(
                            $transaction,
                            $taxAmountColumn,
                            0
                        )
                    );

                    $grossAmount = $grossAmountColumn
                        ? (float) $group->sum(
                            fn (
                                AccountingTaxTransaction $transaction
                            ): float => (float) data_get(
                                $transaction,
                                $grossAmountColumn,
                                0
                            )
                        )
                        : 0.0;

                    /*
                     * Derive missing totals without changing stored records.
                     */
                    if (
                        $taxableAmount === 0.0
                        && $grossAmount !== 0.0
                    ) {
                        $taxableAmount = max(
                            $grossAmount - $taxAmount,
                            0
                        );
                    }

                    if ($grossAmount === 0.0) {
                        $grossAmount =
                            $taxableAmount + $taxAmount;
                    }

                    return (object) [
                        'tax_code' => $taxCode,
                        'direction' => $direction,

                        'transaction_count' =>
                            $group->count(),

                        'taxable_amount' => round(
                            $taxableAmount,
                            2
                        ),

                        'tax_amount' => round(
                            $taxAmount,
                            2
                        ),

                        'gross_amount' => round(
                            $grossAmount,
                            2
                        ),
                    ];
                }
            )
            ->sortBy([
                ['tax_code', 'asc'],
                ['direction', 'asc'],
            ])
            ->values();
    }

    private function cashEquivalentAccountIds(): Collection
    {
        $ids = collect();

        if (
            Schema::hasTable(
                'accounting_account_mappings'
            )
        ) {
            $ids = AccountingAccountMapping::query()
                ->whereIn(
                    'key',
                    [
                        'cash_account',
                        'bank_account',
                        'mpesa_account',
                        'petty_cash_account',
                    ]
                )
                ->whereNotNull('account_id')
                ->pluck('account_id');
        }

        if ($ids->isNotEmpty()) {
            return $ids
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();
        }

        return AccountingAccount::query()
            ->active()
            ->where('type', 'asset')
            ->where(function ($query): void {
                $query
                    ->whereRaw(
                        'LOWER(name) LIKE ?',
                        ['%cash%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%bank%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%m-pesa%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%mpesa%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%paybill%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%till%']
                    )
                    ->orWhereRaw(
                        'LOWER(name) LIKE ?',
                        ['%mobile money%']
                    );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }
}
