<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data): StockAdjustment {
            $rows = collect($data['items'] ?? [])
                ->filter(fn ($row) =>
                    filled($row['inventory_item_id'] ?? null)
                    && filled($row['direction'] ?? null)
                    && (float) ($row['quantity'] ?? 0) > 0
                )
                ->values();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Please add at least one stock adjustment item.',
                ]);
            }

            $adjustment = StockAdjustment::query()->create([
                'adjustment_date' => $data['adjustment_date'] ?? now('Africa/Nairobi')->toDateString(),
                'reason' => $data['reason'] ?? 'manual_correction',
                'notes' => $data['notes'] ?? null,
                'adjusted_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $totalIn = 0;
            $totalOut = 0;
            $totalValue = 0;

            foreach ($rows as $row) {
                $item = InventoryItem::query()->findOrFail($row['inventory_item_id']);

                $direction = $row['direction'];
                $quantity = (float) $row['quantity'];
                $unitCost = (float) ($row['unit_cost'] ?? $item->unit_cost ?? 0);

                if (! in_array($direction, ['in', 'out'], true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid stock adjustment direction selected.',
                    ]);
                }

                $stockBefore = (float) $item->current_stock;

                if ($direction === 'out' && $quantity > $stockBefore) {
                    throw ValidationException::withMessages([
                        'items' => "{$item->name} has insufficient stock. Available: {$stockBefore} {$item->unit}, requested: {$quantity} {$item->unit}.",
                    ]);
                }

                $movement = $direction === 'in'
                    ? app(InventoryLedgerService::class)->recordIn(
                        item: $item,
                        quantity: $quantity,
                        unitCost: $unitCost,
                        type: 'adjustment',
                        movementDate: $adjustment->adjustment_date->toDateString(),
                        referenceable: $adjustment,
                        notes: 'Stock adjustment: ' . $adjustment->adjustment_no,
                    )
                    : app(InventoryLedgerService::class)->recordOut(
                        item: $item,
                        quantity: $quantity,
                        unitCost: $unitCost,
                        type: 'adjustment',
                        movementDate: $adjustment->adjustment_date->toDateString(),
                        referenceable: $adjustment,
                        notes: 'Stock adjustment: ' . $adjustment->adjustment_no,
                    );

                $stockAfter = (float) $item->fresh()->current_stock;
                $lineValue = $quantity * $unitCost;

                $adjustment->items()->create([
                    'inventory_item_id' => $item->id,
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'unit' => $item->unit,
                    'unit_cost' => $unitCost,
                    'line_value' => $lineValue,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => $row['notes'] ?? null,
                ]);

                if ($direction === 'in') {
                    $totalIn += $quantity;
                } else {
                    $totalOut += $quantity;
                }

                $totalValue += $lineValue;
            }

            $adjustment->forceFill([
                'total_in_quantity' => $totalIn,
                'total_out_quantity' => $totalOut,
                'total_value' => $totalValue,
            ])->saveQuietly();

            return $adjustment->refresh();
        });
    }
}
