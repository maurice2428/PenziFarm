<?php
namespace App\Filament\Resources\Accounting\AccountingCostCenterResource\Pages;
use App\Filament\Resources\Accounting\AccountingCostCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAccountingCostCenters extends ListRecords { protected static string $resource = AccountingCostCenterResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
