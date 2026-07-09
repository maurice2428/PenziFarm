<?php

namespace App\Filament\Resources\PurchaseOrderReceiptResource\Pages;

use App\Filament\Resources\PurchaseOrderReceiptResource;
use App\Models\PurchaseOrder;
use App\Services\Procurement\PurchaseReceivingService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseOrderReceipt extends CreateRecord
{
    protected static string $resource = PurchaseOrderReceiptResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $purchaseOrder = PurchaseOrder::query()->findOrFail($data['purchase_order_id']);

        return app(PurchaseReceivingService::class)->receive($purchaseOrder, [
            ...$data,
            'items' => $this->data['items'] ?? [],
        ]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Goods received note saved')
            ->body('Accepted items have been added to stock through stock movements.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
