<?php

namespace App\Filament\Resources\PurchaseOrderReceiptResource\Pages;

use App\Filament\Resources\PurchaseOrderReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderReceipt extends EditRecord
{
    protected static string $resource = PurchaseOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
