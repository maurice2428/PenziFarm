<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingFiscalYear;
use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Accounting\AccountingProjectFundTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    /**
     * @param array<int, array{
     *     account_id:int,
     *     debit?:numeric-string|int|float|null,
     *     credit?:numeric-string|int|float|null,
     *     description?:string|null,
     *     cost_center_id?:int|null,
     *     project_fund_id?:int|null,
     *     party_type?:string|null,
     *     party_id?:int|null,
     *     metadata?:array|null
     * }> $lines
     */
    public function createJournalEntry(array $data, array $lines, bool $postImmediately = false): AccountingJournalEntry
    {
        return DB::transaction(function () use ($data, $lines, $postImmediately) {
            $transactionDate = $this->normalizeDate($data['transaction_date'] ?? now());
            $period = $this->findOpenPeriod($transactionDate);
            $fiscalYear = $period?->fiscalYear ?? $this->findFiscalYear($transactionDate);

            if ($period?->isLocked() || $fiscalYear?->isLocked()) {
                throw ValidationException::withMessages([
                    'transaction_date' => 'This accounting period is closed or locked.',
                ]);
            }

            $preparedLines = $this->prepareAndValidateLines($lines);

            $journal = AccountingJournalEntry::create([
                'journal_number' => $data['journal_number'] ?? $this->nextJournalNumber(),
                'fiscal_year_id' => $fiscalYear?->id,
                'accounting_period_id' => $period?->id,
                'transaction_date' => $transactionDate->toDateString(),
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'narration' => $data['narration'] ?? 'Accounting journal entry',
                'status' => 'draft',
                'total_debit' => $preparedLines['total_debit'],
                'total_credit' => $preparedLines['total_credit'],
                'created_by' => $data['created_by'] ?? Auth::id(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($preparedLines['lines'] as $line) {
                $journal->lines()->create($line);
            }

            if ($postImmediately) {
                $this->postJournalEntry($journal);
            }

            return $journal->refresh()->load(['lines.account', 'accountingPeriod', 'fiscalYear']);
        });
    }

    public function postJournalEntry(AccountingJournalEntry $journal): AccountingJournalEntry
    {
        return DB::transaction(function () use ($journal) {
            $journal->loadMissing('lines.account', 'accountingPeriod', 'fiscalYear');

            if ($journal->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journal entries can be posted.',
                ]);
            }

            if (! $journal->isBalanced()) {
                throw ValidationException::withMessages([
                    'lines' => 'Journal entry is not balanced. Total debits must equal total credits.',
                ]);
            }

            if ($journal->lines->count() < 2) {
                throw ValidationException::withMessages([
                    'lines' => 'A journal entry must have at least two lines.',
                ]);
            }

            if ($journal->accountingPeriod?->isLocked() || $journal->fiscalYear?->isLocked()) {
                throw ValidationException::withMessages([
                    'period' => 'This accounting period is closed or locked.',
                ]);
            }

            $journal->forceFill([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ])->save();

            return $journal->refresh();
        });
    }

    public function reverseJournalEntry(AccountingJournalEntry $journal, ?string $reason = null): AccountingJournalEntry
    {
        return DB::transaction(function () use ($journal, $reason) {
            $journal->loadMissing('lines');

            if ($journal->status !== 'posted') {
                throw ValidationException::withMessages([
                    'status' => 'Only posted journal entries can be reversed.',
                ]);
            }

            $reversal = $this->createJournalEntry([
                'transaction_date' => now(),
                'reference' => 'REV-' . $journal->journal_number,
                'narration' => 'Reversal of ' . $journal->journal_number . ($reason ? ': ' . $reason : ''),
                'source_type' => 'journal_reversal',
                'source_id' => $journal->id,
                'metadata' => [
                    'reversal_of_id' => $journal->id,
                    'reason' => $reason,
                ],
            ], $journal->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'description' => 'Reversal: ' . ($line->description ?: $journal->narration),
                'cost_center_id' => $line->cost_center_id,
                'project_fund_id' => $line->project_fund_id,
                'party_type' => $line->party_type,
                'party_id' => $line->party_id,
                'metadata' => $line->metadata,
            ])->all(), true);

            $journal->forceFill([
                'status' => 'reversed',
                'reversed_by' => Auth::id(),
                'reversed_at' => now(),
            ])->save();

            $reversal->forceFill(['reversal_of_id' => $journal->id])->save();

            return $reversal->refresh();
        });
    }

    public function recordDirectorFunding(array $data): AccountingJournalEntry
    {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $paymentAccount = $this->accountFromMapping($data['payment_account_key'] ?? $this->paymentAccountKey($data['payment_method'] ?? 'bank'));
        $fundingAccount = isset($data['funding_account_id']) && $data['funding_account_id']
            ? AccountingAccount::findOrFail($data['funding_account_id'])
            : $this->accountFromMapping(($data['funding_type'] ?? 'director_capital') === 'director_loan' ? 'director_loan_payable' : 'director_capital');

        return $this->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Director/project funding received',
            'source_type' => 'director_funding',
            'source_id' => $data['source_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ], [
            [
                'account_id' => $paymentAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Funds received',
            ],
            [
                'account_id' => $fundingAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Funding source credited',
            ],
        ], true);
    }

    public function allocateProjectFund(AccountingProjectFund $projectFund, array $data): AccountingProjectFundTransaction
    {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $journal = $this->recordDirectorFunding([
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'funding_type' => $projectFund->fundingSource?->type ?? 'director_capital',
            'funding_account_id' => $projectFund->fundingSource?->linked_account_id,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? $projectFund->fund_code,
            'narration' => $data['narration'] ?? 'Project funds received for ' . $projectFund->name,
            'metadata' => [
                'project_fund_id' => $projectFund->id,
            ],
        ]);

        return AccountingProjectFundTransaction::create([
            'transaction_number' => $data['transaction_number'] ?? $this->nextProjectTransactionNumber(),
            'project_fund_id' => $projectFund->id,
            'funding_source_id' => $projectFund->funding_source_id,
            'journal_entry_id' => $journal->id,
            'transaction_type' => 'receipt',
            'transaction_date' => $this->normalizeDate($data['transaction_date'] ?? now())->toDateString(),
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Project funding received',
            'created_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function recordProjectExpense(AccountingProjectFund $projectFund, array $data): AccountingProjectFundTransaction
    {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $expenseAccount = AccountingAccount::findOrFail($data['expense_account_id']);
        $paymentAccount = isset($data['payment_account_id'])
            ? AccountingAccount::findOrFail($data['payment_account_id'])
            : $this->accountFromMapping($this->paymentAccountKey($data['payment_method'] ?? 'bank'));

        $journal = $this->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? $projectFund->fund_code,
            'narration' => $data['narration'] ?? 'Project expense for ' . $projectFund->name,
            'source_type' => 'project_expense',
            'source_id' => $projectFund->id,
            'metadata' => [
                'project_fund_id' => $projectFund->id,
            ],
        ], [
            [
                'account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => $data['description'] ?? 'Project expense',
                'project_fund_id' => $projectFund->id,
                'cost_center_id' => $projectFund->cost_center_id,
            ],
            [
                'account_id' => $paymentAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Payment for project expense',
                'project_fund_id' => $projectFund->id,
                'cost_center_id' => $projectFund->cost_center_id,
            ],
        ], true);

        return AccountingProjectFundTransaction::create([
            'transaction_number' => $data['transaction_number'] ?? $this->nextProjectTransactionNumber(),
            'project_fund_id' => $projectFund->id,
            'funding_source_id' => $projectFund->funding_source_id,
            'journal_entry_id' => $journal->id,
            'transaction_type' => 'expense',
            'transaction_date' => $this->normalizeDate($data['transaction_date'] ?? now())->toDateString(),
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'reference' => $data['reference'] ?? null,
            'narration' => $data['narration'] ?? 'Project expense paid',
            'created_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function accountFromMapping(string $key): AccountingAccount
    {
        $mapping = AccountingAccountMapping::query()->with('account')->where('key', $key)->first();

        if (! $mapping?->account) {
            throw ValidationException::withMessages([
                'account_mapping' => "Missing accounting account mapping for [{$key}].",
            ]);
        }

        return $mapping->account;
    }

    public function findOpenPeriod(CarbonInterface|string $date): ?AccountingPeriod
    {
        $date = $this->normalizeDate($date)->toDateString();

        return AccountingPeriod::query()
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('fiscalYear')
            ->first();
    }

    public function findFiscalYear(CarbonInterface|string $date): ?AccountingFiscalYear
    {
        $date = $this->normalizeDate($date)->toDateString();

        return AccountingFiscalYear::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public function nextJournalNumber(): string
    {
        $prefix = 'JE-' . now()->format('Ym') . '-';
        $last = AccountingJournalEntry::query()
            ->where('journal_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->latest('id')
            ->value('journal_number');

        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function nextProjectTransactionNumber(): string
    {
        $prefix = 'PFT-' . now()->format('Ym') . '-';
        $last = AccountingProjectFundTransaction::query()
            ->where('transaction_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->latest('id')
            ->value('transaction_number');

        $next = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return array{lines: array<int, array<string, mixed>>, total_debit: float, total_credit: float}
     */
    private function prepareAndValidateLines(array $lines): array
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A journal entry must have at least two lines.',
            ]);
        }

        $prepared = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Debit and credit amounts cannot be negative.',
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'A single journal line cannot have both debit and credit amounts.',
                ]);
            }

            if ($debit <= 0 && $credit <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Each journal line must have either a debit or a credit amount.',
                ]);
            }

            AccountingAccount::query()->active()->findOrFail($line['account_id']);

            $totalDebit += $debit;
            $totalCredit += $credit;

            $prepared[] = [
                'account_id' => $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
                'project_fund_id' => $line['project_fund_id'] ?? null,
                'party_type' => $line['party_type'] ?? null,
                'party_id' => $line['party_id'] ?? null,
                'metadata' => $line['metadata'] ?? null,
            ];
        }

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry is not balanced. Total debits must equal total credits.',
            ]);
        }

        return [
            'lines' => $prepared,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    private function normalizeDate(CarbonInterface|string $date): Carbon
    {
        return $date instanceof CarbonInterface ? Carbon::parse($date->toDateTimeString()) : Carbon::parse($date);
    }

    private function positiveAmount(mixed $amount, string $field): float
    {
        $value = round((float) $amount, 2);

        if ($value <= 0) {
            throw ValidationException::withMessages([
                $field => ucfirst($field) . ' must be greater than zero.',
            ]);
        }

        return $value;
    }

    private function paymentAccountKey(string $method): string
    {
        return match ($method) {
            'cash' => 'cash_account',
            'mpesa' => 'mpesa_account',
            'petty_cash' => 'petty_cash_account',
            default => 'bank_account',
        };
    }
}
