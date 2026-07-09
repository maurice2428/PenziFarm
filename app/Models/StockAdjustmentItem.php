<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustmentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stock_adjustment_id',
        'inventory_item_id',
        'direction',
        'quantity',
        'unit',
        'unit_cost',
        'line_value',
        'stock_before',
        'stock_after',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'line_value' => 'decimal:2',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'in' ? 'Stock In' : 'Stock Out';
    }
}
