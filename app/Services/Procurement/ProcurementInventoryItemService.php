<?php

namespace App\Services\Procurement;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProcurementInventoryItemService
{
    public function create(array $data): InventoryItem
    {
        return DB::transaction(
            function () use ($data): InventoryItem {
                if (! Schema::hasTable('inventory_items')) {
                    throw new RuntimeException(
                        'The inventory_items table is missing.'
                    );
                }

                $name = trim(
                    (string) ($data['name'] ?? '')
                );

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'name' => 'Stock item name is required.',
                    ]);
                }

                $query = InventoryItem::query();

                if (
                    in_array(
                        \Illuminate\Database\Eloquent\SoftDeletes::class,
                        class_uses_recursive(InventoryItem::class),
                        true
                    )
                ) {
                    $query->withTrashed();
                }

                $existing = $query
                    ->whereRaw(
                        'LOWER(name) = ?',
                        [mb_strtolower($name)]
                    )
                    ->first();

                if ($existing) {
                    $archived = method_exists(
                        $existing,
                        'trashed'
                    ) && $existing->trashed();

                    throw ValidationException::withMessages([
                        'name' =>
                            'A stock item with this name already '
                            . 'exists'
                            . ($archived
                                ? ' in the archive.'
                                : '.'),
                    ]);
                }

                $openingStock = max(
                    0,
                    (float) ($data['opening_stock'] ?? 0)
                );

                $payload = [
                    'name' => $name,
                    'category' =>
                        $data['category'] ?? 'equipment',
                    'unit' => $data['unit'] ?? 'unit',
                    'opening_stock' => $openingStock,
                    'current_stock' => $openingStock,
                    'available_stock' => $openingStock,
                    'reorder_level' => max(
                        0,
                        (float) (
                            $data['reorder_level'] ?? 0
                        )
                    ),
                    'order_level' => max(
                        0,
                        (float) (
                            $data['order_level'] ?? 0
                        )
                    ),
                    'unit_cost' => max(
                        0,
                        (float) ($data['unit_cost'] ?? 0)
                    ),
                    'expiry_date' =>
                        $data['expiry_date'] ?? null,
                    'is_active' =>
                        (bool) ($data['is_active'] ?? true),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];

                $columns = array_flip(
                    Schema::getColumnListing(
                        'inventory_items'
                    )
                );

                $item = new InventoryItem();

                $item->forceFill(
                    array_intersect_key(
                        $payload,
                        $columns
                    )
                );

                $item->save();

                return $item->refresh();
            }
        );
    }
}
