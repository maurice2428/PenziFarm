<?php

namespace App\Console\Commands;

use App\Models\HR\PayrollPayment;
use App\Services\HR\Payroll\PayrollPaymentService;
use Illuminate\Console\Command;

class RepairPayrollPaymentDraft extends Command
{
    protected $signature =
        'payroll-payments:repair-draft '
        . '{payment : Payroll payment ID}';

    protected $description =
        'Repair or regenerate employee lines for a draft '
        . 'salary payment.';

    public function handle(
        PayrollPaymentService $service
    ): int {
        $payment = PayrollPayment::query()
            ->withTrashed()
            ->find(
                (int) $this->argument(
                    'payment'
                )
            );

        if (! $payment) {
            $this->error(
                'Payroll payment was not found.'
            );

            return self::FAILURE;
        }

        if ($payment->trashed()) {
            $this->error(
                'Archived payroll payments must be restored first.'
            );

            return self::FAILURE;
        }

        if (! $payment->isDraft()) {
            $this->warn(
                'The payment is not a draft. No employee lines '
                . 'were changed.'
            );

            return self::SUCCESS;
        }

        $before = $payment->items()->count();

        $payment = $service->ensureDraftItems(
            $payment
        );

        $rows = $service->formRowsForPayment(
            $payment
        );

        $service->syncDraftItemsFromForm(
            $payment,
            $rows
        );

        $payment->refresh();

        $this->info(
            'Salary payment draft repaired.'
        );

        $this->table(
            ['Metric', 'Value'],
            [
                [
                    'Payment',
                    $payment->payment_number,
                ],
                [
                    'Lines before',
                    $before,
                ],
                [
                    'Lines after',
                    $payment->items()->count(),
                ],
                [
                    'Total amount',
                    'KES '
                    . number_format(
                        (float) $payment
                            ->total_amount,
                        2
                    ),
                ],
            ]
        );

        return self::SUCCESS;
    }
}
