<?php

namespace App\Observers;

use App\Models\HR\LeaveApplication;
use App\Services\DatabaseNotificationService;

class LeaveApplicationObserver
{
    public function updated(LeaveApplication $leave): void
    {
        $actor = auth()->user()?->name ?? 'System';

        $title = 'Leave Application Updated';
        $body = "{$actor} updated leave application #{$leave->id}.";

        if ($leave->wasChanged('approval_status')) {
            $status = $leave->approval_status;

            $statusText = $status instanceof \BackedEnum
                ? $status->value
                : (string) $status;

            $title = 'Leave Status Updated';
            $body = "{$actor} changed leave application #{$leave->id} to {$statusText}.";
        }

        DatabaseNotificationService::send(
            ['Admin', 'Manager', 'Finance'],
            $title,
            $body,
            'heroicon-o-calendar-days',
            'warning'
        );
    }
}
