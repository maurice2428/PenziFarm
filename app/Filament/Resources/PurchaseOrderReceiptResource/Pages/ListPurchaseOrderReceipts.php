<?php

namespace App\Filament\Resources\PurchaseOrderReceiptResource\Pages;

use App\Filament\Resources\PurchaseOrderReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrderReceipts extends ListRecords
{
    protected static string $resource = PurchaseOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Goods Received Note')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }
}
