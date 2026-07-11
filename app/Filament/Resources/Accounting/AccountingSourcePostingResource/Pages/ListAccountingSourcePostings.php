<?php

namespace App\Filament\Resources\Accounting\AccountingSourcePostingResource\Pages;

use App\Filament\Resources\Accounting\AccountingSourcePostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingSourcePostings extends ListRecords
{
    protected static string $resource = AccountingSourcePostingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
