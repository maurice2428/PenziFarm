<?php

namespace App\Filament\Resources\PurchaseOrderPaymentResource\Pages;

use App\Filament\Resources\PurchaseOrderPaymentResource;
use App\Models\PurchaseOrder;
use App\Services\Procurement\ProcurementLifecycleService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseOrderPayment extends CreateRecord
{
    protected static string $resource =
        PurchaseOrderPaymentResource::class;

    protected function handleRecordCreation(
        array $data
    ): Model {
        $purchaseOrder = PurchaseOrder::query()
            ->findOrFail($data['purchase_order_id']);

        return app(
            ProcurementLifecycleService::class
        )->recordPayment($purchaseOrder, $data);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Supplier payment recorded')
            ->body(
                'The purchase order balance has been updated.'
            )
            ->send();
    }
}
