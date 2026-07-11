<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Models\Accounting\AccountingFiscalYear;
use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingOpeningBalance;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingPostingFailure;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Accounting\AccountingProjectFundTransaction;
use App\Models\Accounting\AccountingSourcePosting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountingService
{
    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function createJournalEntry(
        array $data,
        array $lines,
        bool $postImmediately = false,
        bool $autoApprove = false,
    ): AccountingJournalEntry {
        return DB::transaction(function () use (
            $data,
            $lines,
            $postImmediately,
            $autoApprove,
        ): AccountingJournalEntry {
            $transactionDate = $this->normalizeDate(
                $data['transaction_date'] ?? now('Africa/Nairobi')
            );

            $period = $this->findOpenPeriod($transactionDate);

            if (! $period) {
                throw ValidationException::withMessages([
                    'transaction_date' =>
                        'No open accounting period covers '
                        . $transactionDate->format('d M Y')
                        . '. Open the period before posting.',
                ]);
            }

            $fiscalYear = $period->fiscalYear;

            if (! $fiscalYear || $fiscalYear->isLocked()) {
                throw ValidationException::withMessages([
                    'transaction_date' =>
                        'The related fiscal year is closed or locked.',
                ]);
            }

            $postingKey = $data['posting_key']
                ?? $this->buildPostingKey(
                    $data['source_type'] ?? null,
                    $data['source_id'] ?? null,
                    $data['source_action'] ?? 'recognition'
                );

            if (filled($postingKey)) {
                $existing = AccountingJournalEntry::query()
                    ->withTrashed()
                    ->where('posting_key', $postingKey)
                    ->first();

                if ($existing) {
                    return $existing->loadMissing([
                        'lines.account',
                        'accountingPeriod',
                        'fiscalYear',
                    ]);
                }
            }

            $prepared = $this->prepareAndValidateLines(
                $lines,
                ($data['source_type'] ?? 'manual') === 'manual'
            );

            $journal = AccountingJournalEntry::query()->create([
                'journal_number' =>
                    $data['journal_number']
                    ?? $this->nextJournalNumber(),
                'posting_key' => $postingKey,
                'fiscal_year_id' => $fiscalYear->id,
                'accounting_period_id' => $period->id,
                'transaction_date' => $transactionDate->toDateString(),
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'source_reference' =>
                    $data['source_reference']
                    ?? $data['reference']
                    ?? null,
                'reference' => $data['reference'] ?? null,
                'narration' =>
                    $data['narration']
                    ?? 'Accounting journal entry',
                'currency_code' =>
                    strtoupper($data['currency_code'] ?? 'KES'),
                'exchange_rate' =>
                    (float) ($data['exchange_rate'] ?? 1),
                'status' => 'draft',
                'total_debit' => $prepared['total_debit'],
                'total_credit' => $prepared['total_credit'],
                'created_by' => $data['created_by'] ?? Auth::id(),
                'approved_by' => $autoApprove ? Auth::id() : null,
                'approved_at' => $autoApprove ? now() : null,
                'approval_notes' => $autoApprove
                    ? 'System-approved source transaction.'
                    : null,
                'metadata' => array_merge(
                    $data['metadata'] ?? [],
                    [
                        'source_action' =>
                            $data['source_action'] ?? 'recognition',
                    ]
                ),
            ]);

            foreach ($prepared['lines'] as $line) {
                $journal->lines()->create($line);
            }

            if (filled($postingKey)) {
                $sourcePosting = AccountingSourcePosting::query()
                    ->firstOrNew(['posting_key' => $postingKey]);

                $sourcePosting->forceFill([
                    'source_type' =>
                        $data['source_type'] ?? 'manual',
                    'source_id' => $data['source_id'] ?? null,
                    'source_action' =>
                        $data['source_action'] ?? 'recognition',
                    'source_reference' =>
                        $data['source_reference']
                        ?? $data['reference']
                        ?? null,
                    'journal_entry_id' => $journal->id,
                    'status' => 'draft',
                    'attempts' => (int) $sourcePosting->attempts + 1,
                    'last_error' => null,
                    'metadata' => $data['metadata'] ?? null,
                ])->save();
            }

            if ($postImmediately) {
                $journal = $this->postJournalEntry(
                    $journal,
                    bypassApproval: $autoApprove
                );
            }

            return $journal->refresh()->load([
                'lines.account',
                'accountingPeriod',
                'fiscalYear',
            ]);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function updateDraftJournal(
        AccountingJournalEntry $journal,
        array $data,
        array $lines
    ): AccountingJournalEntry {
        return DB::transaction(function () use (
            $journal,
            $data,
            $lines
        ): AccountingJournalEntry {
            $locked = AccountingJournalEntry::query()
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journals can be edited.',
                ]);
            }

            $transactionDate = $this->normalizeDate(
                $data['transaction_date']
                ?? $locked->transaction_date
            );

            $period = $this->findOpenPeriod($transactionDate);

            if (! $period || $period->isLocked()) {
                throw ValidationException::withMessages([
                    'transaction_date' =>
                        'The selected date is not in an open period.',
                ]);
            }

            $prepared = $this->prepareAndValidateLines(
                $lines,
                ($locked->source_type ?? 'manual') === 'manual'
            );

            $locked->forceFill([
                'transaction_date' => $transactionDate->toDateString(),
                'fiscal_year_id' => $period->fiscal_year_id,
                'accounting_period_id' => $period->id,
                'reference' => $data['reference'] ?? null,
                'source_reference' =>
                    $data['source_reference']
                    ?? $data['reference']
                    ?? null,
                'narration' => $data['narration'] ?? $locked->narration,
                'currency_code' => strtoupper(
                    $data['currency_code'] ?? 'KES'
                ),
                'exchange_rate' => (float) (
                    $data['exchange_rate'] ?? 1
                ),
                'total_debit' => $prepared['total_debit'],
                'total_credit' => $prepared['total_credit'],
                'approved_by' => null,
                'approved_at' => null,
                'approval_notes' => null,
                'lock_version' => (int) $locked->lock_version + 1,
            ])->save();

            $locked->lines()->delete();

            foreach ($prepared['lines'] as $line) {
                $locked->lines()->create($line);
            }

            return $locked->refresh()->load('lines.account');
        });
    }

    public function approveJournalEntry(
        AccountingJournalEntry $journal,
        ?string $notes = null
    ): AccountingJournalEntry {
        return DB::transaction(function () use (
            $journal,
            $notes
        ): AccountingJournalEntry {
            $locked = AccountingJournalEntry::query()
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journals can be approved.',
                ]);
            }

            if (! $locked->isBalanced()) {
                throw ValidationException::withMessages([
                    'lines' => 'The journal is not balanced.',
                ]);
            }

            $locked->forceFill([
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $notes,
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            return $locked->refresh();
        });
    }

    public function postJournalEntry(
        AccountingJournalEntry $journal,
        bool $bypassApproval = false
    ): AccountingJournalEntry {
        return DB::transaction(function () use (
            $journal,
            $bypassApproval
        ): AccountingJournalEntry {
            $locked = AccountingJournalEntry::query()
                ->lockForUpdate()
                ->with([
                    'lines.account',
                    'accountingPeriod',
                    'fiscalYear',
                ])
                ->findOrFail($journal->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft journals can be posted.',
                ]);
            }

            if (! $locked->isBalanced()) {
                throw ValidationException::withMessages([
                    'lines' =>
                        'Total debits must equal total credits.',
                ]);
            }

            if ($locked->lines->count() < 2) {
                throw ValidationException::withMessages([
                    'lines' =>
                        'A journal entry must contain at least two lines.',
                ]);
            }

            if (
                $locked->accountingPeriod?->isLocked()
                || $locked->fiscalYear?->isLocked()
            ) {
                throw ValidationException::withMessages([
                    'period' =>
                        'The accounting period or fiscal year is locked.',
                ]);
            }

            $requiresApproval =
                ($locked->source_type ?? 'manual') === 'manual'
                || (bool) data_get(
                    $locked->metadata,
                    'requires_approval',
                    false
                );

            if (
                $requiresApproval
                && ! $bypassApproval
                && ! $locked->isApproved()
            ) {
                throw ValidationException::withMessages([
                    'approval' =>
                        'Approve the journal before posting it.',
                ]);
            }

            $locked->forceFill([
                'status' => 'posted',
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            if (filled($locked->posting_key)) {
                AccountingSourcePosting::query()
                    ->where('posting_key', $locked->posting_key)
                    ->update([
                        'status' => 'posted',
                        'journal_entry_id' => $locked->id,
                        'posted_at' => now(),
                        'last_error' => null,
                    ]);
            }

            return $locked->refresh();
        });
    }

    public function reverseJournalEntry(
        AccountingJournalEntry $journal,
        ?string $reason = null,
        CarbonInterface|string|null $transactionDate = null,
    ): AccountingJournalEntry {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $journal,
            $reason,
            $transactionDate,
        ): AccountingJournalEntry {
            $locked = AccountingJournalEntry::query()
                ->lockForUpdate()
                ->with('lines')
                ->findOrFail($journal->getKey());

            if (! $locked->isPosted()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only posted journals can be reversed.',
                ]);
            }

            if ($locked->reversal()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'This journal already has a reversal.',
                ]);
            }

            $reversalDate = $transactionDate
                ? $this->normalizeDate($transactionDate)
                : now('Africa/Nairobi');

            $reversal = $this->createJournalEntry([
                'transaction_date' => $reversalDate,
                'reference' => 'REV-' . $locked->journal_number,
                'source_reference' => $locked->source_reference,
                'narration' =>
                    'Reversal of '
                    . $locked->journal_number
                    . ': '
                    . $reason,
                'source_type' => 'journal_reversal',
                'source_id' => $locked->id,
                'source_action' => 'reversal',
                'posting_key' => 'journal-reversal:' . $locked->id,
                'metadata' => [
                    'reversal_of_id' => $locked->id,
                    'reason' => $reason,
                    'original_posting_key' => $locked->posting_key,
                ],
            ], $locked->lines->map(
                fn ($line): array => [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' =>
                        'Reversal: '
                        . ($line->description ?: $locked->narration),
                    'cost_center_id' => $line->cost_center_id,
                    'project_fund_id' => $line->project_fund_id,
                    'party_type' => $line->party_type,
                    'party_id' => $line->party_id,
                    'party_pin' => $line->party_pin,
                    'party_name' => $line->party_name,
                    'tax_code' => $line->tax_code,
                    'tax_rate' => $line->tax_rate,
                    'tax_amount' => $line->tax_amount,
                    'etims_document_number' =>
                        $line->etims_document_number,
                    'metadata' => $line->metadata,
                ]
            )->all(), postImmediately: true, autoApprove: true);

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by' => Auth::id(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            $reversal->forceFill([
                'reversal_of_id' => $locked->id,
            ])->save();

            if (filled($locked->posting_key)) {
                AccountingSourcePosting::query()
                    ->where('posting_key', $locked->posting_key)
                    ->update([
                        'status' => 'reversed',
                        'reversed_at' => now(),
                    ]);
            }

            return $reversal->refresh();
        });
    }

    public function deleteDraftJournal(
        AccountingJournalEntry $journal
    ): void {
        DB::transaction(function () use ($journal): void {
            $locked = AccountingJournalEntry::query()
                ->lockForUpdate()
                ->findOrFail($journal->getKey());

            if (! $locked->canBeDeletedSafely()) {
                throw ValidationException::withMessages([
                    'journal' =>
                        'Only unlinked draft journals can be deleted.',
                ]);
            }

            $locked->delete();
        });
    }

    public function postOpeningBalances(
        AccountingFiscalYear $fiscalYear,
        array $openingBalanceIds
    ): AccountingJournalEntry {
        return DB::transaction(function () use (
            $fiscalYear,
            $openingBalanceIds
        ): AccountingJournalEntry {
            $balances = AccountingOpeningBalance::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->whereIn('id', $openingBalanceIds)
                ->where('status', 'draft')
                ->with('account')
                ->lockForUpdate()
                ->get();

            if ($balances->isEmpty()) {
                throw ValidationException::withMessages([
                    'opening_balances' =>
                        'Select at least one draft opening balance.',
                ]);
            }

            $lines = $balances->map(fn ($balance): array => [
                'account_id' => $balance->account_id,
                'debit' => (float) $balance->debit,
                'credit' => (float) $balance->credit,
                'description' =>
                    'Opening balance: '
                    . $balance->account?->code
                    . ' '
                    . $balance->account?->name,
            ])->all();

            $debits = round($balances->sum('debit'), 2);
            $credits = round($balances->sum('credit'), 2);
            $difference = round($debits - $credits, 2);

            if (abs($difference) >= 0.01) {
                $retained = $this->accountFromMapping(
                    'retained_earnings'
                );

                $lines[] = [
                    'account_id' => $retained->id,
                    'debit' => $difference < 0 ? abs($difference) : 0,
                    'credit' => $difference > 0 ? abs($difference) : 0,
                    'description' =>
                        'Opening balance equity balancing entry',
                ];
            }

            $journal = $this->createJournalEntry([
                'transaction_date' => $fiscalYear->start_date,
                'reference' => 'OPEN-' . $fiscalYear->id,
                'source_reference' => $fiscalYear->name,
                'narration' =>
                    'Opening balances for ' . $fiscalYear->name,
                'source_type' => 'opening_balance',
                'source_id' => $fiscalYear->id,
                'source_action' => 'opening',
                'posting_key' =>
                    'opening-balance:'
                    . $fiscalYear->id
                    . ':'
                    . hash('sha256', implode(',', $balances->pluck('id')->all())),
            ], $lines, postImmediately: true, autoApprove: true);

            AccountingOpeningBalance::query()
                ->whereIn('id', $balances->pluck('id'))
                ->update([
                    'status' => 'posted',
                    'journal_entry_id' => $journal->id,
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                ]);

            return $journal;
        });
    }

    public function recordDirectorFunding(array $data): AccountingJournalEntry
    {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $paymentAccount = $this->accountFromMapping(
            $data['payment_account_key']
            ?? $this->paymentAccountKey(
                $data['payment_method'] ?? 'bank'
            )
        );

        $fundingAccount = filled($data['funding_account_id'] ?? null)
            ? AccountingAccount::query()->findOrFail(
                $data['funding_account_id']
            )
            : $this->accountFromMapping(
                ($data['funding_type'] ?? 'director_capital')
                    === 'director_loan'
                    ? 'director_loan_payable'
                    : 'director_capital'
            );

        return $this->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'narration' =>
                $data['narration']
                ?? 'Director or project funding received',
            'source_type' => 'director_funding',
            'source_id' => $data['source_id'] ?? null,
            'posting_key' => $data['posting_key'] ?? null,
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
        ], postImmediately: true, autoApprove: true);
    }

    public function allocateProjectFund(
        AccountingProjectFund $projectFund,
        array $data
    ): AccountingProjectFundTransaction {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');

        $journal = $this->recordDirectorFunding([
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'funding_type' =>
                $projectFund->fundingSource?->type
                ?? 'director_capital',
            'funding_account_id' =>
                $projectFund->fundingSource?->linked_account_id,
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? $projectFund->fund_code,
            'narration' =>
                $data['narration']
                ?? 'Project funds received for ' . $projectFund->name,
            'posting_key' =>
                'project-fund-receipt:'
                . $projectFund->id
                . ':'
                . Str::uuid(),
            'metadata' => ['project_fund_id' => $projectFund->id],
        ]);

        $transaction = AccountingProjectFundTransaction::query()->create([
            'transaction_number' =>
                $data['transaction_number']
                ?? $this->nextProjectTransactionNumber(),
            'project_fund_id' => $projectFund->id,
            'funding_source_id' => $projectFund->funding_source_id,
            'journal_entry_id' => $journal->id,
            'transaction_type' => 'receipt',
            'transaction_date' => $this->normalizeDate(
                $data['transaction_date'] ?? now()
            )->toDateString(),
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'reference' => $data['reference'] ?? null,
            'narration' =>
                $data['narration'] ?? 'Project funding received',
            'created_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'metadata' => $data['metadata'] ?? null,
        ]);

        $projectFund->refreshBalances();

        return $transaction;
    }

    public function recordProjectExpense(
        AccountingProjectFund $projectFund,
        array $data
    ): AccountingProjectFundTransaction {
        $amount = $this->positiveAmount($data['amount'] ?? 0, 'amount');
        $expenseAccount = AccountingAccount::query()->findOrFail(
            $data['expense_account_id']
        );
        $paymentAccount = filled($data['payment_account_id'] ?? null)
            ? AccountingAccount::query()->findOrFail(
                $data['payment_account_id']
            )
            : $this->accountFromMapping(
                $this->paymentAccountKey(
                    $data['payment_method'] ?? 'bank'
                )
            );

        $journal = $this->createJournalEntry([
            'transaction_date' => $data['transaction_date'] ?? now(),
            'reference' => $data['reference'] ?? $projectFund->fund_code,
            'narration' =>
                $data['narration']
                ?? 'Project expense for ' . $projectFund->name,
            'source_type' => 'project_fund_expense',
            'source_id' => $projectFund->id,
            'posting_key' =>
                'project-fund-expense:'
                . $projectFund->id
                . ':'
                . Str::uuid(),
            'metadata' => ['project_fund_id' => $projectFund->id],
        ], [
            [
                'account_id' => $expenseAccount->id,
                'debit' => $amount,
                'credit' => 0,
                'description' =>
                    $data['description'] ?? 'Project expenditure',
                'cost_center_id' => $projectFund->cost_center_id,
                'project_fund_id' => $projectFund->id,
            ],
            [
                'account_id' => $paymentAccount->id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Project payment',
                'cost_center_id' => $projectFund->cost_center_id,
                'project_fund_id' => $projectFund->id,
            ],
        ], postImmediately: true, autoApprove: true);

        $transaction = AccountingProjectFundTransaction::query()->create([
            'transaction_number' =>
                $data['transaction_number']
                ?? $this->nextProjectTransactionNumber(),
            'project_fund_id' => $projectFund->id,
            'funding_source_id' => $projectFund->funding_source_id,
            'journal_entry_id' => $journal->id,
            'transaction_type' => 'expense',
            'transaction_date' => $this->normalizeDate(
                $data['transaction_date'] ?? now()
            )->toDateString(),
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'bank',
            'reference' => $data['reference'] ?? null,
            'narration' =>
                $data['narration'] ?? 'Project expense',
            'created_by' => Auth::id(),
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'metadata' => $data['metadata'] ?? null,
        ]);

        $projectFund->refreshBalances();

        return $transaction;
    }

    public function accountFromMapping(string $key): AccountingAccount
    {
        $mapping = AccountingAccountMapping::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->with('account')
            ->first();

        if (! $mapping?->account || ! $mapping->account->is_active) {
            throw ValidationException::withMessages([
                'account_mapping' =>
                    "Accounting mapping [{$key}] is missing or inactive.",
            ]);
        }

        return $mapping->account;
    }

    public function findOpenPeriod(
        CarbonInterface|string $date
    ): ?AccountingPeriod {
        $date = $this->normalizeDate($date)->toDateString();

        return AccountingPeriod::query()
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('fiscalYear')
            ->first();
    }

    public function findFiscalYear(
        CarbonInterface|string $date
    ): ?AccountingFiscalYear {
        $date = $this->normalizeDate($date)->toDateString();

        return AccountingFiscalYear::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public function nextJournalNumber(): string
    {
        $date = now('Africa/Nairobi')->format('Ymd');
        $next = (int) AccountingJournalEntry::query()
            ->withTrashed()
            ->max('id') + 1;

        return 'JNL' . $date . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function nextProjectTransactionNumber(): string
    {
        $date = now('Africa/Nairobi')->format('Ymd');
        $next = (int) AccountingProjectFundTransaction::query()->max('id') + 1;

        return 'PFT' . $date . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function buildPostingKey(
        ?string $sourceType,
        int|string|null $sourceId,
        string $action = 'recognition'
    ): ?string {
        if (blank($sourceType) || blank($sourceId)) {
            return null;
        }

        return Str::lower(
            trim($sourceType)
            . ':'
            . trim((string) $sourceId)
            . ':'
            . trim($action)
        );
    }

    public function recordPostingFailure(
        string $sourceType,
        int|string|null $sourceId,
        string $action,
        Throwable $exception,
        ?string $eventName = null,
        array $metadata = []
    ): AccountingPostingFailure {
        $failure = AccountingPostingFailure::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('source_action', $action)
            ->where('status', 'pending')
            ->first();

        $payload = [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_action' => $action,
            'event_name' => $eventName,
            'exception_class' => $exception::class,
            'error_message' => $exception->getMessage(),
            'stack_excerpt' => Str::limit($exception->getTraceAsString(), 4000),
            'status' => 'pending',
            'attempts' => ($failure?->attempts ?? 0) + 1,
            'last_attempted_at' => now(),
            'metadata' => $metadata,
        ];

        return $failure
            ? tap($failure)->update($payload)
            : AccountingPostingFailure::query()->create($payload);
    }

    /** @return array{lines: array<int, array<string, mixed>>, total_debit: float, total_credit: float} */
    private function prepareAndValidateLines(
        array $lines,
        bool $manualPosting
    ): array {
        $prepared = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $index => $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" =>
                        'Debit and credit values cannot be negative.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    "lines.{$index}" =>
                        'Each line must contain either a debit or a credit, not both.',
                ]);
            }

            $account = AccountingAccount::query()
                ->withCount('children')
                ->findOrFail($line['account_id']);

            if (! $account->is_active) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" =>
                        "Account {$account->code} is inactive.",
                ]);
            }

            if ($account->children_count > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" =>
                        "Account {$account->code} is a parent account and cannot receive postings.",
                ]);
            }

            if ($manualPosting && ! $account->allow_manual_posting) {
                throw ValidationException::withMessages([
                    "lines.{$index}.account_id" =>
                        "Account {$account->code} is restricted to system postings.",
                ]);
            }

            if ($account->requires_cost_center && blank($line['cost_center_id'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.{$index}.cost_center_id" =>
                        "Account {$account->code} requires a cost centre.",
                ]);
            }

            if ($account->requires_project && blank($line['project_fund_id'] ?? null)) {
                throw ValidationException::withMessages([
                    "lines.{$index}.project_fund_id" =>
                        "Account {$account->code} requires a project fund.",
                ]);
            }

            $prepared[] = [
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
                'project_fund_id' => $line['project_fund_id'] ?? null,
                'party_type' => $line['party_type'] ?? null,
                'party_id' => $line['party_id'] ?? null,
                'party_pin' => $line['party_pin'] ?? null,
                'party_name' => $line['party_name'] ?? null,
                'tax_code' => $line['tax_code'] ?? null,
                'tax_rate' => $line['tax_rate'] ?? null,
                'tax_amount' => round((float) ($line['tax_amount'] ?? 0), 2),
                'etims_document_number' => $line['etims_document_number'] ?? null,
                'metadata' => $line['metadata'] ?? null,
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (count($prepared) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'At least two journal lines are required.',
            ]);
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw ValidationException::withMessages([
                'lines' =>
                    'Journal is not balanced. Debits: KES '
                    . number_format($totalDebit, 2)
                    . '; Credits: KES '
                    . number_format($totalCredit, 2)
                    . '.',
            ]);
        }

        return [
            'lines' => $prepared,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    private function normalizeDate(
        CarbonInterface|string $date
    ): Carbon {
        return $date instanceof CarbonInterface
            ? Carbon::instance($date)
            : Carbon::parse($date, 'Africa/Nairobi');
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
        return match (strtolower($method)) {
            'cash' => 'cash_account',
            'mpesa', 'm-pesa', 'stk' => 'mpesa_account',
            'petty_cash' => 'petty_cash_account',
            default => 'bank_account',
        };
    }
}
