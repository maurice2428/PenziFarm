<?php

namespace App\Filament\Resources\Accounting\AccountingTaxTransactionResource\Pages;

use App\Filament\Resources\Accounting\AccountingTaxTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingTaxTransactions extends ListRecords
{
    protected static string $resource = AccountingTaxTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
