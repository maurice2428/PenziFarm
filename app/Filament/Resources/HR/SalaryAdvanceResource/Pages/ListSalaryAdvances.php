<?php

namespace App\Filament\Resources\HR\SalaryAdvanceResource\Pages;

use App\Filament\Resources\HR\SalaryAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryAdvances extends ListRecords
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
