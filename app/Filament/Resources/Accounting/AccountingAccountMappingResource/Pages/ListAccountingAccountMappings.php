<?php

namespace App\Filament\Resources\Accounting\AccountingAccountMappingResource\Pages;

use App\Filament\Resources\Accounting\AccountingAccountMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingAccountMappings extends ListRecords
{
    protected static string $resource = AccountingAccountMappingResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
