<?php

namespace App\Filament\Resources\HR\AttendanceRecordResource\Pages;

use App\Filament\Resources\HR\AttendanceRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceRecord extends CreateRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['adjusted_by'] = auth()->id();

        return $data;
    }
}
