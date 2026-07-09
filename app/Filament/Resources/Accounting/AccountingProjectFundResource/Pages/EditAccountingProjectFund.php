<?php
namespace App\Filament\Resources\Accounting\AccountingProjectFundResource\Pages;
use App\Filament\Resources\Accounting\AccountingProjectFundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAccountingProjectFund extends EditRecord { protected static string $resource = AccountingProjectFundResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
