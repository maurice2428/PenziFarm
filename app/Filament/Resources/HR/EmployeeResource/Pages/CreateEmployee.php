<?php

namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Employee number generation belongs to the Employee model/service.
        // Do not hard-code or regenerate the number on this page.
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['is_active'] = ($data['status'] ?? 'active') === 'active';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
