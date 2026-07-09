<?php

namespace App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;

use App\Filament\Resources\Accounting\AccountingJournalEntryResource;
use App\Services\Accounting\AccountingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccountingJournalEntry extends CreateRecord
{
    protected static string $resource = AccountingJournalEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        return app(AccountingService::class)->createJournalEntry($data, $lines, false);
    }
}
