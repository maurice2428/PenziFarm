<?php

namespace App\Filament\Resources\HR\PayrollPaymentResource\Pages;

use App\Filament\Resources\HR\PayrollPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollPayments extends ListRecords
{
    protected static string $resource = PayrollPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('New Salary Payment')];
    }
}
