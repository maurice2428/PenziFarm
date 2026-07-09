<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
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
    ): StockMovement {
        return $this->createMovement(
            item: $item,
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
        );
    }

    public function recordOut(
        InventoryItem $item,
        float $quantity,
        float $unitCost,
        string $type,
        string $movementDate,
        ?Model $referenceable = null,
        ?string $notes = null,
    ): StockMovement {
        $available = $this->availableStock($item);

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'items' => "{$item->name} has insufficient stock. Available: {$available} {$item->unit}, requested: {$quantity} {$item->unit}.",
            ]);
        }

        return $this->createMovement(
            item: $item,
            direction: 'out',
            quantity: $quantity,
            unitCost: $unitCost,
            type: $type,
            movementDate: $movementDate,
            referenceable: $referenceable,
            notes: $notes,
        );
    }

    public function availableStock(InventoryItem $item): float
    {
        $openingStock = (float) ($item->opening_stock ?? 0);

        $stockIn = (float) StockMovement::query()
            ->where('inventory_item_id', $item->id)
            ->where('direction', 'in')
            ->sum('quantity');

        $stockOut = (float) StockMovement::query()
            ->where('inventory_item_id', $item->id)
            ->where('direction', 'out')
            ->sum('quantity');

        return round($openingStock + $stockIn - $stockOut, 3);
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
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock movement quantity must be greater than zero.',
            ]);
        }

        $payload = [
            'inventory_item_id' => $item->id,
            'direction' => $direction,
            'type' => $type,

            // Important for your existing stock_movements table.
            'source' => $type,

            'quantity' => $quantity,
            'unit' => $item->unit,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'movement_date' => $movementDate,
            'referenceable_type' => $referenceable ? $referenceable::class : null,
            'referenceable_id' => $referenceable?->id,
            'purchase_order_id' => $purchaseOrderId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ];

        $payload = collect($payload)
            ->filter(fn ($value, string $column): bool =>
                Schema::hasColumn('stock_movements', $column)
            )
            ->toArray();

        return StockMovement::query()->create($payload);
    }
}
