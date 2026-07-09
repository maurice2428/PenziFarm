<?php
namespace App\Filament\Resources\Accounting\AccountingTaxSettingResource\Pages;
use App\Filament\Resources\Accounting\AccountingTaxSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAccountingTaxSetting extends EditRecord { protected static string $resource = AccountingTaxSettingResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
