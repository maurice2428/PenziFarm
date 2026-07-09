<?php

namespace App\Filament\Resources\CasualPayrollResource\Pages;

use App\Filament\Resources\CasualPayrollResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCasualPayroll extends CreateRecord
{
    protected static string $resource = CasualPayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
