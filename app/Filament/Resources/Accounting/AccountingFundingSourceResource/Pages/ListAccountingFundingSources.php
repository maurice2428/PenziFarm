<?php
namespace App\Filament\Resources\Accounting\AccountingFundingSourceResource\Pages;
use App\Filament\Resources\Accounting\AccountingFundingSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAccountingFundingSources extends ListRecords { protected static string $resource = AccountingFundingSourceResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
