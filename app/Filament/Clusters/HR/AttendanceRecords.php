<?php

namespace App\Filament\Clusters\HR;

use Filament\Clusters\Cluster;

class AttendanceRecords extends Cluster
{
    //protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 5;
}
