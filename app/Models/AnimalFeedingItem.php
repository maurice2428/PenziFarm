<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalFeedingItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_feeding_id',
        'inventory_item_id',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (AnimalFeedingItem $item): void {
            $item->total_cost = (float) $item->quantity * (float) $item->unit_cost;
        });
    }

    public function feeding()
    {
        return $this->belongsTo(AnimalFeeding::class, 'animal_feeding_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
