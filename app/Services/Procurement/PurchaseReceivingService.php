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
    public function formRows(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrder->loadMissing([
            'items.inventoryItem',
            'items.receiptItems',
        ]);

        return $purchaseOrder->items
            ->map(function (PurchaseOrderItem $item): array {
                $accepted = (float) $item
                    ->receiptItems
                    ->sum('accepted_quantity');

                $closedRejected = (float) $item
                    ->receiptItems
                    ->whereIn(
                        'rejection_disposition',
                        [
                            'supplier_credit_note',
                            'supplier_refund',
                            'accepted_short_delivery',
                        ]
                    )
                    ->sum('rejected_quantity');

                $legacyReceived = max(
                    (float) ($item->quantity_received ?? 0),
                    (float) ($item->received_quantity ?? 0)
                );

                $alreadyReceived = max(
                    $accepted,
                    $legacyReceived
                );

                $ordered = (float) (
                    $item->quantity_ordered ?? 0
                );

                $remaining = max(
                    0,
                    $ordered
                        - $alreadyReceived
                        - $closedRejected
                );

                return [
                    'purchase_order_item_id' =>
                        $item->getKey(),
                    'inventory_item_id' =>
                        $item->inventory_item_id,
                    'item_name' =>
                        $item->inventoryItem?->name
                        ?? 'Unknown stock item',
                    'unit' =>
                        $item->inventoryItem?->unit
                        ?? '',
                    'ordered_quantity' => $ordered,
                    'previously_received_quantity' =>
                        $alreadyReceived,
                    'remaining_quantity' => $remaining,
                    'accepted_quantity' => $remaining,
                    'rejected_quantity' => 0,
                    'unit_cost' =>
                        (float) ($item->unit_cost ?? 0),
                    'batch_number' =>
                        $item->batch_number,
                    'expiry_date' =>
                        $item->expiry_date?->format('Y-m-d'),
                    'rejection_reason' => null,
                    'rejection_disposition' => null,
                    'rejection_reference' => null,
                    'notes' => null,
                ];
            })
            ->filter(
                fn (array $row): bool =>
                    (float) $row['remaining_quantity'] > 0
            )
            ->values()
            ->all();
    }

    public function hasRemaining(
        PurchaseOrder $purchaseOrder
    ): bool {
        return count($this->formRows($purchaseOrder)) > 0;
    }

    public function receiveAllRemaining(
        PurchaseOrder $purchaseOrder
    ): ?PurchaseOrderReceipt {
        $rows = $this->formRows($purchaseOrder);

        if ($rows === []) {
            return null;
        }

        return $this->receive($purchaseOrder, [
            'received_date' =>
                now('Africa/Nairobi')->toDateString(),
            'supplier_invoice_no' =>
                $purchaseOrder->supplier_invoice_number,
            'items' => $rows,
            'notes' =>
                'All remaining quantities received.',
        ]);
    }

    public function receive(
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrderReceipt {
        return DB::transaction(function () use (
            $purchaseOrder,
            $data
        ): PurchaseOrderReceipt {
            $lockedOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->getKey());

            $items = collect($data['items'] ?? [])
                ->filter(
                    fn (array $row): bool =>
                        (float) (
                            $row['accepted_quantity'] ?? 0
                        ) > 0
                        || (float) (
                            $row['rejected_quantity'] ?? 0
                        ) > 0
                )
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Enter an accepted or rejected quantity '
                        . 'for at least one item.',
                ]);
            }

            $receipt = PurchaseOrderReceipt::query()->create([
                'purchase_order_id' =>
                    $lockedOrder->getKey(),
                'received_date' =>
                    $data['received_date']
                    ?? now('Africa/Nairobi')->toDateString(),
                'delivery_note_no' =>
                    $data['delivery_note_no'] ?? null,
                'supplier_invoice_no' =>
                    $data['supplier_invoice_no'] ?? null,
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
                    ->where(
                        'purchase_order_id',
                        $lockedOrder->getKey()
                    )
                    ->lockForUpdate()
                    ->with([
                        'inventoryItem',
                        'receiptItems',
                    ])
                    ->findOrFail(
                        $row['purchase_order_item_id']
                    );

                $inventoryItem = InventoryItem::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $poItem->inventory_item_id
                    );

                $rowInventoryId = (int) (
                    $row['inventory_item_id']
                    ?? $poItem->inventory_item_id
                );

                if (
                    $rowInventoryId
                    !== (int) $poItem->inventory_item_id
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'The selected stock item does not '
                            . 'match the purchase-order line.',
                    ]);
                }

                $orderedQuantity = (float) (
                    $poItem->quantity_ordered ?? 0
                );

                $previouslyAccepted = (float) $poItem
                    ->receiptItems()
                    ->sum('accepted_quantity');

                $previouslyClosedRejected =
                    (float) $poItem
                        ->receiptItems()
                        ->whereIn(
                            'rejection_disposition',
                            [
                                'supplier_credit_note',
                                'supplier_refund',
                                'accepted_short_delivery',
                            ]
                        )
                        ->sum('rejected_quantity');

                $legacyReceived = max(
                    (float) (
                        $poItem->quantity_received ?? 0
                    ),
                    (float) (
                        $poItem->received_quantity ?? 0
                    )
                );

                $alreadyReceived = max(
                    $previouslyAccepted,
                    $legacyReceived
                );

                $accepted = max(
                    0,
                    (float) (
                        $row['accepted_quantity'] ?? 0
                    )
                );

                $rejected = max(
                    0,
                    (float) (
                        $row['rejected_quantity'] ?? 0
                    )
                );

                $remainingBefore = max(
                    0,
                    $orderedQuantity
                        - $alreadyReceived
                        - $previouslyClosedRejected
                );

                if (
                    $accepted + $rejected
                    > $remainingBefore
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$inventoryItem->name}: accepted plus "
                            . "rejected quantity exceeds the "
                            . "remaining balance of "
                            . number_format($remainingBefore, 3)
                            . " {$inventoryItem->unit}.",
                    ]);
                }

                $unitCost = max(
                    0,
                    (float) (
                        $row['unit_cost']
                        ?? $poItem->unit_cost
                        ?? 0
                    )
                );

                $lineTotal = $accepted * $unitCost;

                $disposition =
                    $row['rejection_disposition'] ?? null;

                $closedRejected = in_array(
                    $disposition,
                    [
                        'supplier_credit_note',
                        'supplier_refund',
                        'accepted_short_delivery',
                    ],
                    true
                )
                    ? $rejected
                    : 0;

                if (
                    $rejected > 0
                    && blank($row['rejection_reason'] ?? null)
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$inventoryItem->name}: provide a rejection "
                            . "reason for the rejected quantity.",
                    ]);
                }

                if ($rejected > 0 && blank($disposition)) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$inventoryItem->name}: select what should "
                            . "happen to the rejected quantity.",
                    ]);
                }

                if (
                    $rejected > 0
                    && in_array(
                        $disposition,
                        [
                            'returned_to_supplier',
                            'supplier_credit_note',
                            'supplier_refund',
                        ],
                        true
                    )
                    && blank(
                        $row['rejection_reference'] ?? null
                    )
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$inventoryItem->name}: provide the supplier "
                            . "return, credit-note, or refund reference.",
                    ]);
                }

                $balance = max(
                    0,
                    $remainingBefore - $accepted - $closedRejected
                );

                $batchNumber =
                    $row['batch_number']
                    ?? $poItem->batch_number;

                $expiryDate =
                    $row['expiry_date']
                    ?? $poItem->expiry_date?->format('Y-m-d');

                $receipt->items()->create([
                    'purchase_order_item_id' =>
                        $poItem->getKey(),
                    'inventory_item_id' =>
                        $inventoryItem->getKey(),
                    'ordered_quantity' =>
                        $orderedQuantity,
                    'previously_received_quantity' =>
                        $alreadyReceived,
                    'accepted_quantity' => $accepted,
                    'rejected_quantity' => $rejected,
                    'balance_quantity' => $balance,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $expiryDate,
                    'rejection_reason' =>
                        $row['rejection_reason'] ?? null,
                    'rejection_disposition' =>
                        $disposition,
                    'rejection_status' => $rejected > 0
                        ? (
                            $closedRejected > 0
                                ? 'closed'
                                : 'open'
                        )
                        : 'none',
                    'rejection_reference' =>
                        $row['rejection_reference'] ?? null,
                    'rejection_resolved_at' =>
                        $closedRejected > 0
                            ? now('Africa/Nairobi')
                            : null,
                    'rejection_resolved_by' =>
                        $closedRejected > 0
                            ? auth()->id()
                            : null,
                    'notes' => $row['notes'] ?? null,
                ]);

                if ($accepted > 0) {
                    app(InventoryLedgerService::class)
                        ->recordIn(
                            item: $inventoryItem,
                            quantity: $accepted,
                            unitCost: $unitCost,
                            type: 'purchase_receipt',
                            movementDate:
                                $receipt->received_date
                                    ->toDateString(),
                            referenceable: $receipt,
                            purchaseOrderId:
                                $lockedOrder->getKey(),
                            batchNumber: $batchNumber,
                            expiryDate: $expiryDate,
                            notes:
                                'Stock received from '
                                . $lockedOrder
                                    ->purchase_order_number,
                            source: 'procurement',
                        );

                    $inventoryItem->forceFill([
                        'unit_cost' => $unitCost,
                        'expiry_date' => $expiryDate,
                    ])->saveQuietly();
                }

                $this->syncPurchaseOrderItem($poItem);

                $totalAccepted += $accepted;
                $totalRejected += $rejected;
                $totalValue += $lineTotal;
            }

            $this->synchronizeOrder($lockedOrder);

            $freshOrder = $lockedOrder->fresh();

            $receipt->forceFill([
                'total_accepted_quantity' =>
                    round($totalAccepted, 3),
                'total_rejected_quantity' =>
                    round($totalRejected, 3),
                'total_received_value' =>
                    round($totalValue, 2),
                'status' =>
                    $freshOrder?->status === 'received'
                        ? 'received'
                        : 'partial',
            ])->saveQuietly();

            return $receipt->refresh();
        });
    }

    public function syncPurchaseOrderItem(
        PurchaseOrderItem $item
    ): void {
        $accepted = (float) $item
            ->receiptItems()
            ->sum('accepted_quantity');

        $rejected = (float) $item
            ->receiptItems()
            ->sum('rejected_quantity');

        $closedRejected = (float) $item
            ->receiptItems()
            ->whereIn(
                'rejection_disposition',
                [
                    'supplier_credit_note',
                    'supplier_refund',
                    'accepted_short_delivery',
                ]
            )
            ->sum('rejected_quantity');

        $ordered = (float) (
            $item->quantity_ordered ?? 0
        );

        $fulfilled = $accepted + $closedRejected;

        $status = match (true) {
            $fulfilled <= 0 => 'pending',
            $fulfilled < $ordered => 'partial',
            default => 'received',
        };

        $item->forceFill([
            'quantity_received' =>
                round($accepted, 3),
            'received_quantity' =>
                round($accepted, 3),
            'rejected_quantity' =>
                round($rejected, 3),
            'receiving_status' => $status,
        ])->saveQuietly();
    }

    public function synchronizeOrder(
        ?PurchaseOrder $purchaseOrder
    ): void {
        /*
         * Legacy or imported GRNs can be orphaned, and an archived
         * purchase order is hidden by Laravel's normal soft-delete scope.
         * Reversal must never fail with a TypeError in either situation.
         */
        if (! $purchaseOrder) {
            return;
        }

        $resolvedOrder = PurchaseOrder::query()
            ->withTrashed()
            ->find($purchaseOrder->getKey());

        if (! $resolvedOrder) {
            return;
        }

        $purchaseOrder = $resolvedOrder;

        $purchaseOrder->load([
            'items.receiptItems',
        ]);

        if ($purchaseOrder->items->isEmpty()) {
            return;
        }

        $allReceived = true;
        $anyReceived = false;

        foreach ($purchaseOrder->items as $item) {
            $ordered = (float) (
                $item->quantity_ordered ?? 0
            );

            $accepted = (float) $item
                ->receiptItems
                ->sum('accepted_quantity');

            $closedRejected = (float) $item
                ->receiptItems
                ->whereIn(
                    'rejection_disposition',
                    [
                        'supplier_credit_note',
                        'supplier_refund',
                        'accepted_short_delivery',
                    ]
                )
                ->sum('rejected_quantity');

            $fulfilled = $accepted + $closedRejected;

            if ($fulfilled > 0) {
                $anyReceived = true;
            }

            if ($fulfilled < $ordered) {
                $allReceived = false;
            }
        }

        $status = match (true) {
            $allReceived => 'received',
            $anyReceived => 'partially_received',

            /*
             * When the last active GRN is reversed, the order must return
             * to an open ordering state instead of remaining "received".
             */
            $purchaseOrder->status === 'cancelled' => 'cancelled',
            $purchaseOrder->status === 'draft' => 'draft',
            default => 'ordered',
        };

        $purchaseOrder->forceFill([
            'status' => $status,
        ])->saveQuietly();
    }
}
