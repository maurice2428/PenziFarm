<?php

namespace App\Filament\Resources\PurchaseOrderPaymentResource\Pages;

use App\Filament\Resources\PurchaseOrderPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderPayment extends EditRecord
{
    protected static string $resource = PurchaseOrderPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
