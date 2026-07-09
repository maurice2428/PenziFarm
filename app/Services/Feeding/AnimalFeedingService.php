<?php

namespace App\Services\Feeding;

use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnimalFeedingService
{
    public function record(array $data): AnimalFeeding
    {
        return DB::transaction(function () use ($data): AnimalFeeding {
            $animals = $this->resolveAnimals($data);

            if ($animals->isEmpty()) {
                throw ValidationException::withMessages([
                    'target_type' => 'No active animals matched the selected feeding target.',
                ]);
            }

            $items = collect($data['items'] ?? [])
                ->filter(fn($row) => (float) ($row['quantity'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Please add at least one feed item.',
                ]);
            }

            $feeding = AnimalFeeding::query()->create([
                'feeding_date' => $data['feeding_date'] ?? now('Africa/Nairobi')->toDateString(),
                'target_type' => $data['target_type'] ?? 'selected_animals',
                'breed_id' => $data['breed_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'fed_by' => auth()->id(),
                'created_by' => auth()->id(),
                'total_animals' => $animals->count(),
            ]);

            $feeding->animals()->sync($animals->pluck('id')->all());

            $totalQuantity = 0;
            $totalCost = 0;

            foreach ($items as $row) {
                $item = InventoryItem::query()->findOrFail($row['inventory_item_id']);

                $quantity = (float) $row['quantity'];
                $unitCost = (float) ($row['unit_cost'] ?? $item->unit_cost ?? 0);

                $feeding->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit' => $item->unit ?? null,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                    'notes' => $row['notes'] ?? null,
                ]);

                app(InventoryLedgerService::class)->recordOut(
                    item: $item,
                    quantity: $quantity,
                    unitCost: $unitCost,
                    type: 'animal_feeding',
                    movementDate: $feeding->feeding_date->toDateString(),
                    referenceable: $feeding,
                    notes: 'Feed issued to ' . $animals->count() . ' animal(s). Feeding No: ' . $feeding->feeding_no,
                );

                $totalQuantity += $quantity;
                $totalCost += $quantity * $unitCost;
            }

            $feeding->forceFill([
                'total_feed_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
            ])->saveQuietly();

            return $feeding->refresh();
        });
    }

    private function resolveAnimals(array $data)
    {
        $query = Animal::query()
            ->where('status', 'Active')
            ->where(function ($query) {
                $query
                    ->where('is_archived', false)
                    ->orWhereNull('is_archived');
            });

        return match ($data['target_type'] ?? 'selected_animals') {
            'breed' => $query
                ->where('breed_id', $data['breed_id'] ?? 0)
                ->get(),

            'location' => $query
                ->where('current_location_id', $data['location_id'] ?? 0)
                ->get(),

            'all_active' => $query->get(),

            default => $query
                ->whereIn('id', $data['animal_ids'] ?? [])
                ->get(),
        };
    }
}
