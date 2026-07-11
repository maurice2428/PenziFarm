<?php

namespace App\Filament\Resources\Accounting\AccountingOpeningBalanceResource\Pages;

use App\Filament\Resources\Accounting\AccountingOpeningBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingOpeningBalances extends ListRecords
{
    protected static string $resource = AccountingOpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('New Opening Balance')];
    }
}
