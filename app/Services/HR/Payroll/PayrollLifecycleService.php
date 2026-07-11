<?php

namespace App\Services\HR\Payroll;

use App\Models\HR\Payroll;
use App\Models\HR\Payslip;
use App\Services\Accounting\AccountingIntegrationPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PayrollLifecycleService
{
    public function archiveAndReverse(
        Payroll $payroll,
        string $reason
    ): Payroll {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' =>
                    'Provide a clear payroll deletion/reversal '
                    . 'reason of at least five characters.',
            ]);
        }

        return DB::transaction(function () use (
            $payroll,
            $reason
        ): Payroll {
            $locked = Payroll::query()
                ->lockForUpdate()
                ->with([
                    'payments.items',
                    'statutoryRemittances',
                    'items',
                    'payslips',
                ])
                ->findOrFail($payroll->getKey());

            foreach ($locked->payments as $payment) {
                if ($payment->isPosted()) {
                    app(PayrollPaymentService::class)
                        ->reverse(
                            $payment,
                            'Payroll archived: ' . $reason
                        );

                    continue;
                }

                if ($payment->isDraft()) {
                    $payment->delete();
                }
            }

            foreach (
                $locked->statutoryRemittances as $remittance
            ) {
                if ($remittance->isPosted()) {
                    app(StatutoryRemittanceService::class)
                        ->reverse(
                            $remittance,
                            'Payroll archived: ' . $reason
                        );

                    continue;
                }

                if ($remittance->isDraft()) {
                    $remittance->delete();
                }
            }

            /*
             * Reverse payroll recognition (salary expense and liabilities)
             * when an accounting journal was posted for this payroll.
             */
            app(AccountingIntegrationPostingService::class)
                ->reverseSource(
                    $locked,
                    'Payroll archived: ' . $reason
                );

            /*
             * Payslips are generated documents for this payroll run.
             * Removing them prevents an archived payroll from continuing
             * to expose valid-looking payslips.
             */
            Payslip::query()
                ->where('payroll_id', $locked->getKey())
                ->delete();

            /*
             * Payroll items are retained as the calculation audit trail,
             * but all salary payment state is reset after reversals.
             */
            $locked->items()->update([
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
            ]);

            $locked->forceFill([
                'total_paid' => 0,
                'balance_due' => (float) $locked->total_net,
                'payment_status' => 'unpaid',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now('Africa/Nairobi'),
                'cancellation_reason' => $reason,
            ])->saveQuietly();

            $locked->delete();

            return Payroll::query()
                ->withTrashed()
                ->findOrFail($locked->getKey());
        });
    }

    public function restoreArchived(
        Payroll $payroll
    ): Payroll {
        return DB::transaction(function () use (
            $payroll
        ): Payroll {
            $locked = Payroll::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail(
                    $payroll->getKey()
                );

            if (! $locked->trashed()) {
                return $locked;
            }

            $active = Payroll::query()
                ->where(
                    'month',
                    $locked->month
                )
                ->where(
                    'year',
                    $locked->year
                )
                ->whereKeyNot($locked->getKey())
                ->first();

            if ($active) {
                throw ValidationException::withMessages([
                    'restore' =>
                        'This archived payroll cannot be restored because '
                        . 'an active '
                        . $active->revision_label
                        . ' already exists for '
                        . $locked->period_label
                        . '. Keep this record archived or open the active '
                        . 'revision.',
                ]);
            }

            $locked->restore();

            return $locked->refresh();
        });
    }

    public function hasAccountingAuditHistory(
        Payroll $payroll
    ): bool {
        $payrollId = $payroll->getKey();

        $paymentIds = Schema::hasTable(
            'payroll_payments'
        )
            ? DB::table('payroll_payments')
                ->where(
                    'payroll_id',
                    $payrollId
                )
                ->pluck('id')
            : collect();

        $remittanceIds = Schema::hasTable(
            'statutory_remittances'
        )
            ? DB::table(
                'statutory_remittances'
            )
                ->where(
                    'payroll_id',
                    $payrollId
                )
                ->pluck('id')
            : collect();

        if (
            Schema::hasTable(
                'accounting_source_postings'
            )
        ) {
            $hasSourcePosting = DB::table(
                'accounting_source_postings'
            )
                ->where(function ($query) use (
                    $payrollId,
                    $paymentIds,
                    $remittanceIds
                ): void {
                    $query->where(function (
                        $query
                    ) use ($payrollId): void {
                        $query
                            ->where(
                                'source_type',
                                'payroll'
                            )
                            ->where(
                                'source_id',
                                $payrollId
                            );
                    });

                    if ($paymentIds->isNotEmpty()) {
                        $query->orWhere(function (
                            $query
                        ) use ($paymentIds): void {
                            $query
                                ->where(
                                    'source_type',
                                    'payroll_payment'
                                )
                                ->whereIn(
                                    'source_id',
                                    $paymentIds
                                );
                        });
                    }

                    if (
                        $remittanceIds->isNotEmpty()
                    ) {
                        $query->orWhere(function (
                            $query
                        ) use (
                            $remittanceIds
                        ): void {
                            $query
                                ->where(
                                    'source_type',
                                    'statutory_remittance'
                                )
                                ->whereIn(
                                    'source_id',
                                    $remittanceIds
                                );
                        });
                    }
                })
                ->exists();

            if ($hasSourcePosting) {
                return true;
            }
        }

        if (
            Schema::hasTable(
                'accounting_journal_entries'
            )
            && Schema::hasColumn(
                'accounting_journal_entries',
                'source_type'
            )
            && Schema::hasColumn(
                'accounting_journal_entries',
                'source_id'
            )
        ) {
            return DB::table(
                'accounting_journal_entries'
            )
                ->where(
                    'source_type',
                    'payroll'
                )
                ->where(
                    'source_id',
                    $payrollId
                )
                ->exists();
        }

        return false;
    }

    public function canPurgeArchived(
        Payroll $payroll
    ): bool {
        if (! $payroll->trashed()) {
            return false;
        }

        return ! $this->hasAccountingAuditHistory(
            $payroll
        );
    }

    public function purgeArchived(
        Payroll $payroll
    ): void {
        DB::transaction(function () use (
            $payroll
        ): void {
            $locked = Payroll::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail(
                    $payroll->getKey()
                );

            if (! $locked->trashed()) {
                throw ValidationException::withMessages([
                    'purge' =>
                        'Only archived payrolls can be permanently deleted.',
                ]);
            }

            if (
                $this->hasAccountingAuditHistory(
                    $locked
                )
            ) {
                throw ValidationException::withMessages([
                    'purge' =>
                        'This archived payroll has accounting or payment '
                        . 'audit history and cannot be permanently deleted. '
                        . 'It no longer blocks the period: create a new '
                        . 'payroll revision instead.',
                ]);
            }

            $paymentIds = Schema::hasTable(
                'payroll_payments'
            )
                ? DB::table(
                    'payroll_payments'
                )
                    ->where(
                        'payroll_id',
                        $locked->getKey()
                    )
                    ->pluck('id')
                : collect();

            if (
                $paymentIds->isNotEmpty()
                && Schema::hasTable(
                    'payroll_payment_items'
                )
            ) {
                DB::table(
                    'payroll_payment_items'
                )
                    ->whereIn(
                        'payroll_payment_id',
                        $paymentIds
                    )
                    ->delete();
            }

            if (
                Schema::hasTable(
                    'payroll_payments'
                )
            ) {
                DB::table('payroll_payments')
                    ->where(
                        'payroll_id',
                        $locked->getKey()
                    )
                    ->delete();
            }

            if (
                Schema::hasTable(
                    'statutory_remittances'
                )
            ) {
                DB::table(
                    'statutory_remittances'
                )
                    ->where(
                        'payroll_id',
                        $locked->getKey()
                    )
                    ->delete();
            }

            if (Schema::hasTable('payslips')) {
                DB::table('payslips')
                    ->where(
                        'payroll_id',
                        $locked->getKey()
                    )
                    ->delete();
            }

            if (
                Schema::hasTable(
                    'payroll_items'
                )
            ) {
                DB::table('payroll_items')
                    ->where(
                        'payroll_id',
                        $locked->getKey()
                    )
                    ->delete();
            }

            $locked->forceDelete();
        });
    }

}
