<?php

namespace App\Filament\Resources\Accounting\AccountingPostingFailureResource\Pages;

use App\Filament\Resources\Accounting\AccountingPostingFailureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingPostingFailures extends ListRecords
{
    protected static string $resource = AccountingPostingFailureResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
