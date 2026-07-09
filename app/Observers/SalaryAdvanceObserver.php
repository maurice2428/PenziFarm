<?php

namespace App\Observers;

use App\Models\HR\SalaryAdvance;
use App\Services\DatabaseNotificationService;

class SalaryAdvanceObserver
{
    public function updated(SalaryAdvance $advance): void
    {
        $actor = auth()->user()?->name ?? 'System';

        $title = 'Salary Advance Updated';
        $body = "{$actor} updated salary advance #{$advance->id}.";

        if ($advance->wasChanged('approval_status')) {
            $status = $advance->approval_status;

            $statusText = $status instanceof \BackedEnum
                ? $status->value
                : (string) $status;

            $title = 'Salary Advance Status Updated';
            $body = "{$actor} changed salary advance #{$advance->id} to {$statusText}.";
        }

        DatabaseNotificationService::send(
            ['Admin', 'Manager', 'Finance'],
            $title,
            $body,
            'heroicon-o-currency-dollar',
            'warning'
        );
    }
}
