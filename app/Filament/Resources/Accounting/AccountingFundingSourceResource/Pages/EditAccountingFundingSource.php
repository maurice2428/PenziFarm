<?php
namespace App\Filament\Resources\Accounting\AccountingFundingSourceResource\Pages;
use App\Filament\Resources\Accounting\AccountingFundingSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAccountingFundingSource extends EditRecord { protected static string $resource = AccountingFundingSourceResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
