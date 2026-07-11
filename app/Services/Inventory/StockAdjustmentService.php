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
        return DB::transaction(function () use (
            $data
        ): StockAdjustment {
            $rows = collect($data['items'] ?? [])
                ->filter(
                    fn (array $row): bool =>
                        filled(
                            $row['inventory_item_id']
                            ?? null
                        )
                        && filled(
                            $row['direction'] ?? null
                        )
                        && (float) (
                            $row['quantity'] ?? 0
                        ) > 0
                )
                ->values();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Please add at least one stock '
                        . 'adjustment item.',
                ]);
            }

            $adjustment = StockAdjustment::query()->create([
                'adjustment_date' =>
                    $data['adjustment_date']
                    ?? now('Africa/Nairobi')
                        ->toDateString(),
                'reason' =>
                    $data['reason']
                    ?? 'manual_correction',
                'notes' => $data['notes'] ?? null,
                'adjusted_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            $totalIn = 0;
            $totalOut = 0;
            $totalValue = 0;

            foreach ($rows as $row) {
                $item = InventoryItem::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $row['inventory_item_id']
                    );

                $direction = $row['direction'];
                $quantity = abs(
                    (float) $row['quantity']
                );

                $unitCost = max(
                    0,
                    (float) (
                        $row['unit_cost']
                        ?? $item->unit_cost
                        ?? 0
                    )
                );

                if (
                    ! in_array(
                        $direction,
                        ['in', 'out'],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Invalid stock adjustment '
                            . 'direction selected.',
                    ]);
                }

                $stockBefore = app(
                    InventoryLedgerService::class
                )->availableStock($item);

                if (
                    $direction === 'out'
                    && $quantity > $stockBefore
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            "{$item->name} has insufficient "
                            . "stock. Available: "
                            . number_format(
                                $stockBefore,
                                3
                            )
                            . " {$item->unit}; requested: "
                            . number_format(
                                $quantity,
                                3
                            )
                            . " {$item->unit}.",
                    ]);
                }

                $movement = $direction === 'in'
                    ? app(InventoryLedgerService::class)
                        ->recordIn(
                            item: $item,
                            quantity: $quantity,
                            unitCost: $unitCost,
                            type: 'adjustment',
                            movementDate:
                                $adjustment
                                    ->adjustment_date
                                    ->toDateString(),
                            referenceable: $adjustment,
                            notes:
                                'Stock adjustment: '
                                . $adjustment
                                    ->adjustment_no,
                            source:
                                $data['reason']
                                ?? 'manual_correction',
                        )
                    : app(InventoryLedgerService::class)
                        ->recordOut(
                            item: $item,
                            quantity: $quantity,
                            unitCost: $unitCost,
                            type: 'adjustment',
                            movementDate:
                                $adjustment
                                    ->adjustment_date
                                    ->toDateString(),
                            referenceable: $adjustment,
                            notes:
                                'Stock adjustment: '
                                . $adjustment
                                    ->adjustment_no,
                            source:
                                $data['reason']
                                ?? 'manual_correction',
                        );

                $stockAfter = app(
                    InventoryLedgerService::class
                )->availableStock($item);

                $lineValue = $quantity * $unitCost;

                $adjustment->items()->create([
                    'inventory_item_id' =>
                        $item->getKey(),
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'unit' => $item->unit,
                    'unit_cost' => $unitCost,
                    'line_value' => $lineValue,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' =>
                        $row['notes'] ?? null,
                ]);

                if ($direction === 'in') {
                    $totalIn += $quantity;
                } else {
                    $totalOut += $quantity;
                }

                $totalValue += $lineValue;
            }

            $adjustment->forceFill([
                'total_in_quantity' =>
                    round($totalIn, 3),
                'total_out_quantity' =>
                    round($totalOut, 3),
                'total_value' =>
                    round($totalValue, 2),
            ])->saveQuietly();

            return $adjustment->refresh();
        });
    }
}
