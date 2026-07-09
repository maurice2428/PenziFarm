<?php

namespace App\Services\Crops;

use App\Models\CropInputApplication;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CropInputApplicationService
{
    public function create(array $data): CropInputApplication
    {
        return DB::transaction(function () use ($data): CropInputApplication {
            $item = InventoryItem::query()->findOrFail($data['inventory_item_id']);

            $quantity = (float) ($data['quantity'] ?? 0);
            $unitCost = (float) ($data['unit_cost'] ?? $item->unit_cost ?? 0);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            if ((float) $item->current_stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "{$item->name} has insufficient stock. Available: {$item->current_stock} {$item->unit}.",
                ]);
            }

            $application = CropInputApplication::query()->create([
                ...$data,
                'unit' => $data['unit'] ?? $item->unit,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'created_by' => auth()->id(),
            ]);

            app(InventoryLedgerService::class)->recordOut(
                item: $item,
                quantity: $quantity,
                unitCost: $unitCost,
                type: 'crop_input',
                movementDate: $application->application_date->toDateString(),
                referenceable: $application,
                notes: 'Crop input application: ' . $application->application_no,
            );

            $application->cropSeason?->syncCropTotals();
            $application->nurseryBatch?->syncNurseryTotals();

            return $application->refresh();
        });
    }
}
