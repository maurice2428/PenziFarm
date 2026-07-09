<?php

namespace App\Services\Procurement;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceivingService
{
    public function receive(PurchaseOrder $purchaseOrder, array $data): PurchaseOrderReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $data): PurchaseOrderReceipt {
            $items = collect($data['items'] ?? [])
                ->filter(fn ($row) =>
                    (float) ($row['accepted_quantity'] ?? 0) > 0 ||
                    (float) ($row['rejected_quantity'] ?? 0) > 0
                )
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Please receive at least one item.',
                ]);
            }

            $receipt = PurchaseOrderReceipt::query()->create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_date' => $data['received_date'] ?? now('Africa/Nairobi')->toDateString(),
                'delivery_note_no' => $data['delivery_note_no'] ?? null,
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'status' => 'partial',
                'notes' => $data['notes'] ?? null,
                'received_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $totalAccepted = 0;
            $totalRejected = 0;
            $totalValue = 0;

            foreach ($items as $row) {
                $poItem = PurchaseOrderItem::query()
                    ->with(['inventoryItem', 'receiptItems'])
                    ->findOrFail($row['purchase_order_item_id']);

                $inventoryItem = InventoryItem::query()->findOrFail(
                    $row['inventory_item_id'] ?? $poItem->inventory_item_id
                );

                $orderedQuantity = (float) ($poItem->quantity_ordered ?? 0);

                $previouslyAccepted = (float) $poItem->receiptItems()
                    ->sum('accepted_quantity');

                $previouslyRejected = (float) $poItem->receiptItems()
                    ->sum('rejected_quantity');

                $accepted = (float) ($row['accepted_quantity'] ?? 0);
                $rejected = (float) ($row['rejected_quantity'] ?? 0);

                $remainingBefore = max(0, $orderedQuantity - $previouslyAccepted);

                if ($accepted > $remainingBefore) {
                    throw ValidationException::withMessages([
                        'items' => "{$inventoryItem->name}: accepted quantity exceeds remaining balance. Remaining: {$remainingBefore} {$inventoryItem->unit}.",
                    ]);
                }

                $unitCost = (float) ($row['unit_cost'] ?? $poItem->unit_cost ?? 0);
                $lineTotal = $accepted * $unitCost;
                $balance = max(0, $orderedQuantity - $previouslyAccepted - $accepted);

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'ordered_quantity' => $orderedQuantity,
                    'previously_received_quantity' => $previouslyAccepted,
                    'accepted_quantity' => $accepted,
                    'rejected_quantity' => $rejected,
                    'balance_quantity' => $balance,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'batch_number' => $row['batch_number'] ?? $poItem->batch_number,
                    'expiry_date' => $row['expiry_date'] ?? $poItem->expiry_date,
                    'rejection_reason' => $row['rejection_reason'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);

                if ($accepted > 0) {
                    app(InventoryLedgerService::class)->recordIn(
                        item: $inventoryItem,
                        quantity: $accepted,
                        unitCost: $unitCost,
                        type: 'purchase_receipt',
                        movementDate: $receipt->received_date->toDateString(),
                        referenceable: $receipt,
                        purchaseOrderId: $purchaseOrder->id,
                        batchNumber: $row['batch_number'] ?? $poItem->batch_number,
                        expiryDate: $row['expiry_date'] ?? $poItem->expiry_date,
                        notes: 'Received from PO ' . $purchaseOrder->purchase_order_number,
                    );
                }

                $this->syncPurchaseOrderItem($poItem);

                $totalAccepted += $accepted;
                $totalRejected += $rejected;
                $totalValue += $lineTotal;
            }

            $this->syncPurchaseOrder($purchaseOrder);

            $receipt->forceFill([
                'total_accepted_quantity' => $totalAccepted,
                'total_rejected_quantity' => $totalRejected,
                'total_received_value' => $totalValue,
                'status' => $purchaseOrder->fresh()->status === 'received' ? 'received' : 'partial',
            ])->saveQuietly();

            return $receipt->refresh();
        });
    }

    private function syncPurchaseOrderItem(PurchaseOrderItem $item): void
    {
        $accepted = (float) $item->receiptItems()->sum('accepted_quantity');
        $rejected = (float) $item->receiptItems()->sum('rejected_quantity');
        $ordered = (float) ($item->quantity_ordered ?? 0);

        $status = match (true) {
            $accepted <= 0 => 'pending',
            $accepted < $ordered => 'partial',
            default => 'received',
        };

        $item->forceFill([
            'quantity_received' => $accepted,
            'received_quantity' => $accepted,
            'rejected_quantity' => $rejected,
            'receiving_status' => $status,
        ])->saveQuietly();
    }

    private function syncPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items.receiptItems');

        if ($purchaseOrder->items->isEmpty()) {
            return;
        }

        $allReceived = true;
        $anyReceived = false;

        foreach ($purchaseOrder->items as $item) {
            $ordered = (float) ($item->quantity_ordered ?? 0);
            $accepted = (float) $item->receiptItems->sum('accepted_quantity');

            if ($accepted > 0) {
                $anyReceived = true;
            }

            if ($accepted < $ordered) {
                $allReceived = false;
            }
        }

        $status = match (true) {
            $allReceived => 'received',
            $anyReceived => 'partially_received',
            default => $purchaseOrder->status,
        };

        $purchaseOrder->forceFill([
            'status' => $status,
        ])->saveQuietly();
    }
}
