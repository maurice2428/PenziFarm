<?php

namespace App\Filament\Resources\HR\SalaryAdvanceResource\Pages;

use App\Filament\Resources\HR\SalaryAdvanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalaryAdvance extends CreateRecord
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $approved = (float) ($data['amount_approved'] ?? 0);
        $months = max(1, (int) ($data['repayment_months'] ?? 1));

        if ($approved > 0) {
            $data['balance'] = $approved;
            $data['monthly_deduction'] = round($approved / $months, 2);
        } else {
            $data['balance'] = 0;
        }

        return $data;
    }
}
