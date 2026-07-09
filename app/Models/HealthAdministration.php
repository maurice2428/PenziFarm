<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthAdministration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'health_product_id',
        'administered_at',
        'animal_count',
        'dosage_per_animal',
        'total_quantity_used',
        'next_due_date',
        'administered_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'administered_at' => 'date',
        'next_due_date' => 'date',
        'animal_count' => 'integer',
        'dosage_per_animal' => 'decimal:2',
        'total_quantity_used' => 'decimal:2',

    ];

    protected static function booted(): void
    {
        static::creating(function (HealthAdministration $record): void {
            if (auth()->check()) {
                $record->created_by = auth()->id();
            }
        });

        static::saving(function (HealthAdministration $record): void {
            $record->loadMissing('product');

            if ($record->product) {
                $record->next_due_date = $record
                    ->product
                    ->calculateNextDueDate($record->administered_at)
                    ?->toDateString();
            }

            $record->total_quantity_used =
                (float) $record->animal_count * (float) $record->dosage_per_animal;
        });

        static::saved(function (HealthAdministration $record): void {
            $record->syncStockMovement();
        });

        static::deleted(function (HealthAdministration $record): void {
            StockMovement::where('health_administration_id', $record->id)->delete();
        });

        static::restored(function (HealthAdministration $record): void {
            $record->syncStockMovement();
        });
    }

    public function product()
    {
        return $this->belongsTo(HealthProduct::class, 'health_product_id');
    }

    public function animals()
    {
        return $this->belongsToMany(
            Animal::class,
            'health_administration_animals'
        )->withTimestamps();
    }

    public function stockMovement()
    {
        return $this->hasOne(StockMovement::class);
    }

    public function syncStockMovement(): void
    {
        $this->loadMissing('product.inventoryItem');

        if (!$this->product?->inventory_item_id) {
            return;
        }

        if ((float) $this->total_quantity_used <= 0) {
            return;
        }

        StockMovement::updateOrCreate(
            [
                'health_administration_id' => $this->id,
            ],
            [
                'inventory_item_id' => $this->product->inventory_item_id,
                'type' => 'out',
                'source' => $this->product->type,
                'quantity' => -abs((float) $this->total_quantity_used),
                'unit_cost' => $this->product->inventoryItem?->unit_cost ?? 0,
                'movement_date' => $this->administered_at,
                'reference' => 'HEALTH-' . $this->id,
                'notes' => $this->product->name . ' administered to ' . $this->animal_count . ' animal(s).',
                'created_by' => auth()->id(),
            ]
        );
    }
}
