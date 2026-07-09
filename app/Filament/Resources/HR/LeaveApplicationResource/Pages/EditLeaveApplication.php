<?php

namespace App\Filament\Resources\HR\LeaveApplicationResource\Pages;

use App\Filament\Resources\HR\LeaveApplicationResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveApplication extends EditRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $data['days_requested'] = $start->diffInDays($end) + 1;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
