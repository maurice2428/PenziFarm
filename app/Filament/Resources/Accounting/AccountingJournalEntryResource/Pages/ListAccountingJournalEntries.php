<?php

namespace App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;

use App\Filament\Resources\Accounting\AccountingJournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingJournalEntries extends ListRecords
{
    protected static string $resource = AccountingJournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('New Manual Journal')];
    }
}
