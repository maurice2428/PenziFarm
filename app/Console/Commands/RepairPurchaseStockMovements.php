<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPurchaseStockMovements extends Command
{
    protected $signature =
        'inventory:repair-purchase-receipts
        {--commit : Create missing Stock In movements}
        {--purchase-order= : Purchase order ID or number}';

    protected $description =
        'Audit and optionally repair received purchase-order '
        . 'quantities that do not have matching Stock In movements.';

    public function handle(
        InventoryLedgerService $ledger
    ): int {
        $commit = (bool) $this->option('commit');
        $purchaseOrderOption =
            $this->option('purchase-order');

        $query = PurchaseOrder::query()
            ->withTrashed()
            ->with([
                'items.inventoryItem',
                'items.receiptItems',
            ])
            ->orderBy('id');

        if (filled($purchaseOrderOption)) {
            $query->where(function ($query) use (
                $purchaseOrderOption
            ): void {
                $query
                    ->where(
                        'id',
                        $purchaseOrderOption
                    )
                    ->orWhere(
                        'purchase_order_number',
                        $purchaseOrderOption
                    )
                    ->orWhere(
                        'invoice_number',
                        $purchaseOrderOption
                    );
            });
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn(
                'No matching purchase orders were found.'
            );

            return self::SUCCESS;
        }

        $repairs = [];
        $repairCount = 0;

        foreach ($orders as $order) {
            $groups = $order->items
                ->groupBy('inventory_item_id');

            foreach ($groups as $inventoryId => $lines) {
                $item = $lines
                    ->first()
                    ?->inventoryItem;

                if (! $item) {
                    continue;
                }

                $target = 0;
                $weightedValue = 0;

                foreach ($lines as $line) {
                    $receiptAccepted = (float) $line
                        ->receiptItems
                        ->sum('accepted_quantity');

                    $fieldReceived = max(
                        (float) (
                            $line->quantity_received ?? 0
                        ),
                        (float) (
                            $line->received_quantity ?? 0
                        )
                    );

                    $lineTarget = max(
                        $receiptAccepted,
                        $fieldReceived
                    );

                    /*
                     * The old receiveStock() method could mark a PO as
                     * received without setting line quantities or creating
                     * movements. For those legacy records, infer full receipt.
                     */
                    if (
                        $lineTarget <= 0
                        && $order->status === 'received'
                    ) {
                        $lineTarget = (float) (
                            $line->quantity_ordered ?? 0
                        );
                    }

                    $target += $lineTarget;

                    $weightedValue +=
                        $lineTarget
                        * (float) (
                            $line->unit_cost ?? 0
                        );
                }

                $posted = (float) StockMovement::query()
                    ->where(
                        'purchase_order_id',
                        $order->getKey()
                    )
                    ->where(
                        'inventory_item_id',
                        $inventoryId
                    )
                    ->where('direction', 'in')
                    ->sum('quantity');

                $missing = round(
                    $target - $posted,
                    3
                );

                if ($missing <= 0.0005) {
                    continue;
                }

                $unitCost = $target > 0
                    ? $weightedValue / $target
                    : (float) ($item->unit_cost ?? 0);

                $repairs[] = [
                    $order->purchase_order_number,
                    $item->name,
                    number_format($target, 3),
                    number_format($posted, 3),
                    number_format($missing, 3),
                    $commit ? 'REPAIR' : 'DRY RUN',
                ];

                if (! $commit) {
                    continue;
                }

                DB::transaction(function () use (
                    $order,
                    $lines,
                    $item,
                    $missing,
                    $unitCost,
                    $ledger
                ): void {
                    $lockedItem = InventoryItem::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $item->getKey()
                        );

                    $firstLine = $lines->first();

                    $ledger->recordIn(
                        item: $lockedItem,
                        quantity: $missing,
                        unitCost: $unitCost,
                        type: 'purchase_receipt_repair',
                        movementDate:
                            now('Africa/Nairobi')
                                ->toDateString(),
                        referenceable: $order,
                        purchaseOrderId:
                            $order->getKey(),
                        batchNumber:
                            $firstLine?->batch_number,
                        expiryDate:
                            $firstLine?->expiry_date
                                ?->format('Y-m-d'),
                        notes:
                            'Repair of missing Stock In for '
                            . $order
                                ->purchase_order_number,
                        source: 'procurement_repair',
                    );

                    foreach ($lines as $line) {
                        $receiptAccepted =
                            (float) $line
                                ->receiptItems
                                ->sum(
                                    'accepted_quantity'
                                );

                        $fieldReceived = max(
                            (float) (
                                $line
                                    ->quantity_received
                                ?? 0
                            ),
                            (float) (
                                $line
                                    ->received_quantity
                                ?? 0
                            )
                        );

                        $lineTarget = max(
                            $receiptAccepted,
                            $fieldReceived
                        );

                        if (
                            $lineTarget <= 0
                            && $order->status
                                === 'received'
                        ) {
                            $lineTarget = (float) (
                                $line
                                    ->quantity_ordered
                                ?? 0
                            );
                        }

                        $line->forceFill([
                            'quantity_received' =>
                                round(
                                    $lineTarget,
                                    3
                                ),
                            'received_quantity' =>
                                round(
                                    $lineTarget,
                                    3
                                ),
                            'receiving_status' =>
                                $lineTarget
                                >= (float) (
                                    $line
                                        ->quantity_ordered
                                    ?? 0
                                )
                                    ? 'received'
                                    : (
                                        $lineTarget > 0
                                            ? 'partial'
                                            : 'pending'
                                    ),
                        ])->saveQuietly();
                    }

                    $lockedItem->forceFill([
                        'unit_cost' =>
                            max(0, $unitCost),
                        'expiry_date' =>
                            $firstLine?->expiry_date,
                    ])->saveQuietly();
                });

                $repairCount++;
            }
        }

        if ($repairs === []) {
            $this->info(
                'No missing purchase Stock In movements were found.'
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'Purchase Order',
                'Stock Item',
                'Expected',
                'Posted',
                'Missing',
                'Mode',
            ],
            $repairs
        );

        if (! $commit) {
            $this->warn(
                'Dry run only. Re-run with --commit to create '
                . 'the missing Stock In movements.'
            );
        } else {
            $this->info(
                "{$repairCount} missing Stock In movement group(s) repaired."
            );
        }

        return self::SUCCESS;
    }
}
