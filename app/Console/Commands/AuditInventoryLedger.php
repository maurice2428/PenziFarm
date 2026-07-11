<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class AuditInventoryLedger extends Command
{
    protected $signature =
        'inventory:audit-ledger
        {--item= : Inventory item ID or name}
        {--only-problems : Show only negative or malformed ledgers}';

    protected $description =
        'Audit opening stock, Stock In, Stock Out and current stock.';

    public function handle(): int
    {
        $query = InventoryItem::query()
            ->orderBy('name');

        if (filled($this->option('item'))) {
            $item = $this->option('item');

            $query->where(function ($query) use (
                $item
            ): void {
                $query
                    ->where('id', $item)
                    ->orWhere(
                        'name',
                        'like',
                        "%{$item}%"
                    );
            });
        }

        $rows = [];

        foreach ($query->get() as $item) {
            $opening = (float) (
                $item->opening_stock ?? 0
            );

            $stockIn = (float) StockMovement::query()
                ->where(
                    'inventory_item_id',
                    $item->getKey()
                )
                ->where('direction', 'in')
                ->sum('quantity');

            $stockOut = (float) StockMovement::query()
                ->where(
                    'inventory_item_id',
                    $item->getKey()
                )
                ->where('direction', 'out')
                ->sum('quantity');

            $invalid = StockMovement::query()
                ->where(
                    'inventory_item_id',
                    $item->getKey()
                )
                ->where(function ($query): void {
                    $query
                        ->whereNotIn(
                            'direction',
                            ['in', 'out']
                        )
                        ->orWhere(
                            'quantity',
                            '<=',
                            0
                        );
                })
                ->count();

            $current = round(
                $opening + $stockIn - $stockOut,
                3
            );

            $problem = $current < 0 || $invalid > 0;

            if (
                $this->option('only-problems')
                && ! $problem
            ) {
                continue;
            }

            $rows[] = [
                $item->getKey(),
                $item->name,
                number_format($opening, 3),
                number_format($stockIn, 3),
                number_format($stockOut, 3),
                number_format($current, 3),
                $invalid,
                $problem ? 'CHECK' : 'OK',
            ];
        }

        $this->table(
            [
                'ID',
                'Stock Item',
                'Opening',
                'Stock In',
                'Stock Out',
                'Current',
                'Invalid',
                'Status',
            ],
            $rows
        );

        return self::SUCCESS;
    }
}
