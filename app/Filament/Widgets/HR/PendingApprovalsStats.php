<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\LeaveApplication;
use App\Models\HR\SalaryAdvance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovalsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Leave', LeaveApplication::where('approval_status', 'pending')->count()),
            Stat::make('Pending Advances', SalaryAdvance::where('approval_status', 'pending')->count()),
        ];
    }
}
