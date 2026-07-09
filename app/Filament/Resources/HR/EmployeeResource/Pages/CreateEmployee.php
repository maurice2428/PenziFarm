<?php

namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
{
    // Get last employee number
    $lastEmployee = \App\Models\HR\Employee::orderByDesc('id')->first();

    $prefix = 'LLKSTF';
    $nextNumber = 1;

    if ($lastEmployee && $lastEmployee->employee_number) {
        // Extract numeric part
        $lastNumber = (int) str_replace($prefix, '', $lastEmployee->employee_number);
        $nextNumber = $lastNumber + 1;
    }

    // Format with leading zeros
    $data['employee_number'] = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    // Existing logic
    $data['created_by'] = auth()->id();
    $data['updated_by'] = auth()->id();
    $data['is_active'] = ($data['status'] ?? null) === 'active';

    return $data;
}
}
