<?php
namespace App\Filament\Resources\Accounting\OperatingExpensePaymentResource\Pages;
use App\Filament\Resources\Accounting\OperatingExpensePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListOperatingExpensePayments extends ListRecords
{
    protected static string $resource = OperatingExpensePaymentResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
