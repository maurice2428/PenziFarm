<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Staff', Employee::where('status', 'active')->count()),
            Stat::make('Exited Staff', Employee::where('status', 'exited')->count()),
            Stat::make('Total Employees', Employee::count()),
        ];
    }
}
