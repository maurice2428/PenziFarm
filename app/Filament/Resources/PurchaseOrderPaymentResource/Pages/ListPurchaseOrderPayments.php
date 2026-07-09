<?php

namespace App\Filament\Resources\PurchaseOrderPaymentResource\Pages;

use App\Filament\Resources\PurchaseOrderPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrderPayments extends ListRecords
{
    protected static string $resource = PurchaseOrderPaymentResource::class;

    protected function getHeaderActions(): array
    {
         return [
            Actions\CreateAction::make()
                ->label('New Payment')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }
}
