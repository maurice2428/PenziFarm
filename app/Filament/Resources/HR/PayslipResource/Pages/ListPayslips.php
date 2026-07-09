<?php

namespace App\Filament\Resources\HR\PayslipResource\Pages;

use App\Filament\Resources\HR\PayslipResource;
use Filament\Resources\Pages\ListRecords;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
