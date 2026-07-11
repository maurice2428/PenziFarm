<?php

namespace App\Filament\Resources\Accounting\AccountingReconciliationResource\Pages;

use App\Filament\Resources\Accounting\AccountingReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingReconciliations extends ListRecords
{
    protected static string $resource = AccountingReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('New Reconciliation')];
    }
}
