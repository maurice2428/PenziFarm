<?php

namespace App\Filament\Resources\HR\LeaveApplicationResource\Pages;

use App\Filament\Resources\HR\LeaveApplicationResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $data['days_requested'] = $start->diffInDays($end) + 1;

        return $data;
    }
}
