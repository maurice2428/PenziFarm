<?php

namespace App\Observers;

use App\Models\HR\Payroll;
use App\Services\DatabaseNotificationService;

class PayrollObserver
{
    public function updated(Payroll $payroll): void
    {
        $actor = auth()->user()?->name ?? 'System';

        $title = 'Payroll Updated';
        $body = "{$actor} updated payroll {$payroll->month}/{$payroll->year}.";

        if ($payroll->wasChanged('status')) {
            $status = $payroll->status;

            $statusText = $status instanceof \BackedEnum
                ? $status->value
                : (string) $status;

            $title = 'Payroll Status Updated';
            $body = "{$actor} changed payroll {$payroll->month}/{$payroll->year} to {$statusText}.";
        }

        DatabaseNotificationService::send(
            ['Admin', 'Manager', 'Finance'],
            $title,
            $body,
            'heroicon-o-banknotes',
            'success'
        );
    }
}
