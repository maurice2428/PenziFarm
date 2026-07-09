<?php

namespace App\Filament\Resources\Accounting\AccountingAccountResource\Pages;

use App\Filament\Resources\Accounting\AccountingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingAccounts extends ListRecords
{
    protected static string $resource = AccountingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Account'),
        ];
    }
}
