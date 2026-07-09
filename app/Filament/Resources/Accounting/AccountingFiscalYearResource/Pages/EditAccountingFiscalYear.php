<?php

namespace App\Filament\Resources\Accounting\AccountingFiscalYearResource\Pages;

use App\Filament\Resources\Accounting\AccountingFiscalYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingFiscalYear extends EditRecord
{
    protected static string $resource = AccountingFiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
