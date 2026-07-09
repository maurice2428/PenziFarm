<?php

namespace App\Filament\Resources\Accounting\AccountingFiscalYearResource\Pages;

use App\Filament\Resources\Accounting\AccountingFiscalYearResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingFiscalYears extends ListRecords
{
    protected static string $resource = AccountingFiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
