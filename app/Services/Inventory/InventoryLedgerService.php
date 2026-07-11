<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryLedgerService
{
    public function recordIn(
        InventoryItem $item,
        float $quantity,
        float $unitCost,
        string $type,
        string $movementDate,
        ?Model $referenceable = null,
        ?int $purchaseOrderId = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $notes = null,
        ?string $source = null,
    ): StockMovement {
        return DB::transaction(function () use (
            $item,
            $quantity,
            $unitCost,
            $type,
            $movementDate,
            $referenceable,
            $purchaseOrderId,
            $batchNumber,
            $expiryDate,
            $notes,
            $source,
        ): StockMovement {
            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            return $this->createMovement(
                item: $lockedItem,
                direction: 'in',
                quantity: $quantity,
                unitCost: $unitCost,
                type: $type,
                movementDate: $movementDate,
                referenceable: $referenceable,
                purchaseOrderId: $purchaseOrderId,
                batchNumber: $batchNumber,
                expiryDate: $expiryDate,
                notes: $notes,
                source: $source,
            );
        });
    }

    public function recordOut(
        InventoryItem $item,
        float $quantity,
        float $unitCost,
        string $type,
        string $movementDate,
        ?Model $referenceable = null,
        ?int $purchaseOrderId = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $notes = null,
        ?string $source = null,
    ): StockMovement {
        return DB::transaction(function () use (
            $item,
            $quantity,
            $unitCost,
            $type,
            $movementDate,
            $referenceable,
            $purchaseOrderId,
            $batchNumber,
            $expiryDate,
            $notes,
            $source,
        ): StockMovement {
            $lockedItem = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($item->getKey());

            $available = $this->availableStock($lockedItem);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'items' =>
                        "{$lockedItem->name} has insufficient stock. "
                        . "Available: {$available} {$lockedItem->unit}; "
                        . "requested: {$quantity} {$lockedItem->unit}.",
                ]);
            }

            return $this->createMovement(
                item: $lockedItem,
                direction: 'out',
                quantity: $quantity,
                unitCost: $unitCost,
                type: $type,
                movementDate: $movementDate,
                referenceable: $referenceable,
                purchaseOrderId: $purchaseOrderId,
                batchNumber: $batchNumber,
                expiryDate: $expiryDate,
                notes: $notes,
                source: $source,
            );
        });
    }

    public function availableStock(InventoryItem $item): float
    {
        $openingStock = (float) ($item->opening_stock ?? 0);

        $stockIn = (float) StockMovement::query()
            ->where('inventory_item_id', $item->getKey())
            ->where('direction', 'in')
            ->sum('quantity');

        $stockOut = (float) StockMovement::query()
            ->where('inventory_item_id', $item->getKey())
            ->where('direction', 'out')
            ->sum('quantity');

        return round(
            $openingStock + $stockIn - $stockOut,
            3
        );
    }

    private function createMovement(
        InventoryItem $item,
        string $direction,
        float $quantity,
        float $unitCost,
        string $type,
        string $movementDate,
        ?Model $referenceable = null,
        ?int $purchaseOrderId = null,
        ?string $batchNumber = null,
        ?string $expiryDate = null,
        ?string $notes = null,
        ?string $source = null,
    ): StockMovement {
        $quantity = abs($quantity);
        $unitCost = max(0, $unitCost);

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Stock movement quantity must be greater than zero.',
            ]);
        }

        if (! in_array($direction, ['in', 'out'], true)) {
            throw ValidationException::withMessages([
                'direction' =>
                    'Stock movement direction must be Stock In or Stock Out.',
            ]);
        }

        $payload = [
            'inventory_item_id' => $item->getKey(),
            'direction' => $direction,
            'type' => $type,
            'source' => $source ?: $type,
            'quantity' => $quantity,
            'unit' => $item->unit,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'movement_date' => $movementDate,
            'referenceable_type' =>
                $referenceable ? $referenceable::class : null,
            'referenceable_id' => $referenceable?->getKey(),
            'purchase_order_id' => $purchaseOrderId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ];

        $payload = collect($payload)
            ->filter(
                fn (
                    mixed $value,
                    string $column
                ): bool => Schema::hasColumn(
                    'stock_movements',
                    $column
                )
            )
            ->all();

        return StockMovement::query()->create($payload);
    }
}
