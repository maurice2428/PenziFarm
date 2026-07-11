<?php

namespace App\Services\Finance;

use App\Models\Finance\OperatingExpense;
use App\Models\Finance\OperatingExpensePayment;
use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperatingExpenseService
{
    public function approveAndPost(
        OperatingExpense $expense
    ): OperatingExpense {
        return DB::transaction(function () use (
            $expense
        ): OperatingExpense {
            $locked = OperatingExpense::query()
                ->lockForUpdate()
                ->with(['category.account', 'supplier'])
                ->findOrFail($expense->getKey());

            if (! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft operating expenses can be approved.',
                ]);
            }

            if ((float) $locked->net_amount <= 0) {
                throw ValidationException::withMessages([
                    'net_amount' =>
                        'Expense amount must be greater than zero.',
                ]);
            }

            if (! $locked->category?->account) {
                throw ValidationException::withMessages([
                    'expense_category_id' =>
                        'The expense category has no accounting account.',
                ]);
            }

            if (
                $locked->category->requires_etims
                && blank($locked->etims_invoice_number)
            ) {
                throw ValidationException::withMessages([
                    'etims_invoice_number' =>
                        'An eTIMS invoice number is required for this '
                        . 'expense category.',
                ]);
            }

            $locked->forceFill([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now('Africa/Nairobi'),
            ])->save();

            app(AccountingIntegrationPostingService::class)
                ->postModel($locked, 'operating-expense-approval');

            return $locked->refresh();
        });
    }

    public function recordAndPostPayment(
        OperatingExpense $expense,
        array $data
    ): OperatingExpensePayment {
        return DB::transaction(function () use (
            $expense,
            $data
        ): OperatingExpensePayment {
            $lockedExpense = OperatingExpense::query()
                ->lockForUpdate()
                ->findOrFail($expense->getKey());

            if (! $lockedExpense->isApproved()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Approve the expense before recording payment.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            $balance = (float) $lockedExpense->balance_due;

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > $balance + 0.01) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment exceeds the expense balance of KES '
                        . number_format($balance, 2)
                        . '.',
                ]);
            }

            $payment = OperatingExpensePayment::query()->create([
                'operating_expense_id' =>
                    $lockedExpense->getKey(),
                'payment_date' =>
                    $data['payment_date']
                    ?? now('Africa/Nairobi'),
                'amount' => $amount,
                'payment_method' =>
                    $data['payment_method'] ?? 'bank',
                'transaction_reference' =>
                    $data['transaction_reference'] ?? null,
                'mpesa_phone' =>
                    $data['mpesa_phone'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now('Africa/Nairobi'),
            ]);

            app(AccountingIntegrationPostingService::class)
                ->postModel($payment, 'operating-expense-payment');

            $this->synchronizeExpense($lockedExpense);

            return $payment->refresh();
        });
    }

    public function postDraftPayment(
        OperatingExpensePayment $payment
    ): OperatingExpensePayment {
        return DB::transaction(function () use (
            $payment
        ): OperatingExpensePayment {
            $locked = OperatingExpensePayment::query()
                ->lockForUpdate()
                ->with('expense')
                ->findOrFail($payment->getKey());

            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft expense payments can be posted.',
                ]);
            }

            if (! $locked->expense?->isApproved()) {
                throw ValidationException::withMessages([
                    'operating_expense_id' =>
                        'Approve the expense before posting its payment.',
                ]);
            }

            if (
                (float) $locked->amount
                > (float) $locked->expense->balance_due + 0.01
            ) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment exceeds the remaining expense balance.',
                ]);
            }

            $locked->forceFill([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now('Africa/Nairobi'),
            ])->saveQuietly();

            app(AccountingIntegrationPostingService::class)
                ->postModel($locked, 'operating-expense-payment');

            $this->synchronizeExpense($locked->expense);

            return $locked->refresh();
        });
    }

    public function reversePayment(
        OperatingExpensePayment $payment,
        string $reason
    ): OperatingExpensePayment {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reversal_reason' =>
                    'An expense payment reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $payment,
            $reason
        ): OperatingExpensePayment {
            $locked = OperatingExpensePayment::query()
                ->lockForUpdate()
                ->with('expense')
                ->findOrFail($payment->getKey());

            if ($locked->status !== 'posted') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only posted expense payments can be reversed.',
                ]);
            }

            app(AccountingIntegrationPostingService::class)
                ->reverseSource($locked, $reason);

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now('Africa/Nairobi'),
                'reversal_reason' => $reason,
            ])->saveQuietly();

            $this->synchronizeExpense($locked->expense);

            return $locked->refresh();
        });
    }

    public function reverseExpense(
        OperatingExpense $expense,
        string $reason
    ): OperatingExpense {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reversal_reason' =>
                    'An expense reversal reason is required.',
            ]);
        }

        return DB::transaction(function () use (
            $expense,
            $reason
        ): OperatingExpense {
            $locked = OperatingExpense::query()
                ->lockForUpdate()
                ->findOrFail($expense->getKey());

            if (! $locked->isApproved()) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only approved expenses can be reversed.',
                ]);
            }

            if ($locked->hasPostedPayments()) {
                throw ValidationException::withMessages([
                    'payments' =>
                        'Reverse all posted expense payments before '
                        . 'reversing the expense.',
                ]);
            }

            app(AccountingIntegrationPostingService::class)
                ->reverseSource($locked, $reason);

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now('Africa/Nairobi'),
                'reversal_reason' => $reason,
            ])->saveQuietly();

            return $locked->refresh();
        });
    }

    public function synchronizeExpense(
        OperatingExpense $expense
    ): void {
        $paid = (float) $expense->payments()
            ->where('status', 'posted')
            ->sum('amount');

        $payable = (float) $expense->payable_amount;
        $balance = max(0, $payable - $paid);

        $status = match (true) {
            $expense->status === 'reversed' => 'reversed',
            $paid <= 0 => 'approved',
            $balance > 0.01 => 'partially_paid',
            default => 'paid',
        };

        $expense->forceFill([
            'paid_amount' => round($paid, 2),
            'balance_due' => round($balance, 2),
            'status' => $status,
        ])->saveQuietly();
    }
}
