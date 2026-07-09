<?php

namespace App\Filament\Pages\HR;

use Filament\Pages\Page;

class Attendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Attendance';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.hr.attendance';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
