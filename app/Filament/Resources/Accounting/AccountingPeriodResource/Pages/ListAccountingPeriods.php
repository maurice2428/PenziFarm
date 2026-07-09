<?php

namespace App\Filament\Resources\Accounting\AccountingPeriodResource\Pages;

use App\Filament\Resources\Accounting\AccountingPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountingPeriods extends ListRecords
{
    protected static string $resource = AccountingPeriodResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
