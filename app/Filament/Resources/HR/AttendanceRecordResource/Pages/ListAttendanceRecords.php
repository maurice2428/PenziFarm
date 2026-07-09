<?php

namespace App\Filament\Resources\HR\AttendanceRecordResource\Pages;

use App\Filament\Resources\HR\AttendanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceRecords extends ListRecords
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
