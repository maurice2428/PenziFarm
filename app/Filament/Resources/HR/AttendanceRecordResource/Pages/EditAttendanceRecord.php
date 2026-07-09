<?php

namespace App\Filament\Resources\HR\AttendanceRecordResource\Pages;

use App\Filament\Resources\HR\AttendanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceRecord extends EditRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['adjusted_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
