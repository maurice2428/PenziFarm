<?php

namespace App\Filament\Resources\HR\SalaryAdvanceResource\Pages;

use App\Filament\Resources\HR\SalaryAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryAdvance extends EditRecord
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $approved = (float) ($data['amount_approved'] ?? 0);
        $months = max(1, (int) ($data['repayment_months'] ?? 1));

        if (($data['approval_status'] ?? 'pending') === 'approved' && $approved > 0) {
            $data['balance'] = $data['balance'] ?? $approved;
            $data['monthly_deduction'] = round($approved / $months, 2);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
