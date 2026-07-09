<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\AttendanceRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceTodayStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->toDateString();

        return [
            Stat::make('Present Today', AttendanceRecord::whereDate('attendance_date', $today)->where('status', 'present')->count()),
            Stat::make('Absent Today', AttendanceRecord::whereDate('attendance_date', $today)->where('status', 'absent')->count()),
            Stat::make('On Leave Today', AttendanceRecord::whereDate('attendance_date', $today)->where('status', 'on_leave')->count()),
        ];
    }
}
