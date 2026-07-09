<?php
namespace App\Filament\Resources\Accounting\AccountingTaxSettingResource\Pages;
use App\Filament\Resources\Accounting\AccountingTaxSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAccountingTaxSettings extends ListRecords { protected static string $resource = AccountingTaxSettingResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
