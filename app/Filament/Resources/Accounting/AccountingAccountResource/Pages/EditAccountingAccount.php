<?php

namespace App\Filament\Resources\Accounting\AccountingAccountResource\Pages;

use App\Filament\Resources\Accounting\AccountingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingAccount extends EditRecord
{
    protected static string $resource = AccountingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn (): bool => ! (bool) $this->record->is_system),
        ];
    }
}
