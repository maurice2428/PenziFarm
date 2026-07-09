<?php

namespace App\Observers;

use App\Models\HR\Employee;
use App\Services\DatabaseNotificationService;

class EmployeeObserver
{
    public function updated(Employee $employee): void
    {
        $actor = auth()->user()?->name ?? 'System';

        $title = 'Employee Record Updated';
        $body = "{$actor} updated employee {$employee->full_name}.";

        if ($employee->wasChanged('status')) {
            $status = $employee->status;

            $statusText = $status instanceof \BackedEnum
                ? $status->value
                : (string) $status;

            $title = 'Employee Status Updated';
            $body = "{$actor} changed employee {$employee->full_name} status to {$statusText}.";
        }

        DatabaseNotificationService::send(
            ['Admin', 'Manager', 'Finance'],
            $title,
            $body,
            'heroicon-o-user-group',
            'info'
        );
    }
}
