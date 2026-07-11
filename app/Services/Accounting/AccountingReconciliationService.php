<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingReconciliation;
use App\Models\Accounting\AccountingJournalEntryLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingReconciliationService
{
    public function refreshSystemBalance(
        AccountingReconciliation $reconciliation
    ): AccountingReconciliation {
        $account = $reconciliation->account;

        $query = AccountingJournalEntryLine::query()
            ->where('account_id', $reconciliation->account_id)
            ->whereHas('journalEntry', function ($query) use ($reconciliation): void {
                $query
                    ->where('status', 'posted')
                    ->whereDate(
                        'transaction_date',
                        '<=',
                        $reconciliation->statement_date
                    );
            });

        $debits = (float) (clone $query)->sum('debit');
        $credits = (float) (clone $query)->sum('credit');
        $systemBalance = $account->signedBalance($debits, $credits);
        $difference = round(
            (float) $reconciliation->statement_balance - $systemBalance,
            2
        );

        $reconciliation->forceFill([
            'system_balance' => $systemBalance,
            'closing_balance' => $systemBalance,
            'difference' => $difference,
        ])->save();

        return $reconciliation->refresh();
    }

    public function approve(
        AccountingReconciliation $reconciliation
    ): AccountingReconciliation {
        $reconciliation = $this->refreshSystemBalance($reconciliation);

        $reconciliation->forceFill([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ])->save();

        return $reconciliation->refresh();
    }

    public function complete(
        AccountingReconciliation $reconciliation
    ): AccountingReconciliation {
        return DB::transaction(function () use ($reconciliation): AccountingReconciliation {
            $locked = AccountingReconciliation::query()
                ->lockForUpdate()
                ->findOrFail($reconciliation->id);

            $locked = $this->refreshSystemBalance($locked);

            if (abs((float) $locked->difference) >= 0.01) {
                throw ValidationException::withMessages([
                    'difference' =>
                        'The reconciliation difference must be zero before completion.',
                ]);
            }

            $locked->forceFill([
                'status' => 'reconciled',
                'reconciled_by' => Auth::id(),
                'reconciled_at' => now(),
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }

    public function reopen(
        AccountingReconciliation $reconciliation
    ): AccountingReconciliation {
        $reconciliation->forceFill([
            'status' => 'draft',
            'reconciled_by' => null,
            'reconciled_at' => null,
            'completed_by' => null,
            'completed_at' => null,
        ])->save();

        return $reconciliation->refresh();
    }
}
