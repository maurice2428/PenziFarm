<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'opening_stock',
        'reorder_level',
        'order_level',
        'unit_cost',
        'expiry_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'opening_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'order_level' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryItem $record): void {
            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }
        });
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function healthProducts()
    {
        return $this->hasMany(HealthProduct::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function feedingItems()
    {
        return $this->hasMany(AnimalFeedingItem::class);
    }

    public function getCurrentStockAttribute(): float
    {
        $opening = (float) ($this->opening_stock ?? 0);

        $stockIn = (float) $this->stockMovements()
            ->where('direction', 'in')
            ->whereNull('deleted_at')
            ->sum('quantity');

        $stockOut = (float) $this->stockMovements()
            ->where('direction', 'out')
            ->whereNull('deleted_at')
            ->sum('quantity');

        return round($opening + $stockIn - $stockOut, 3);
    }

    public function getLedgerStockAttribute(): float
    {
        return $this->current_stock;
    }

    public function getStockValueAttribute(): float
    {
        return round($this->current_stock * (float) ($this->unit_cost ?? 0), 2);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= (float) ($this->reorder_level ?? 0);
    }

    public function getNeedsOrderingAttribute(): bool
    {
        return $this->current_stock <= (float) ($this->order_level ?? 0);
    }

    public function getCategoryLabelAttribute(): string
    {
        return str($this->category ?: 'uncategorized')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getStockDisplayAttribute(): string
    {
        return number_format((float) $this->current_stock, 3) . ' ' . ($this->unit ?: '');
    }

    public function getStockStatusLabelAttribute(): string
    {
        if ($this->is_low_stock) {
            return 'Low Stock';
        }

        if ($this->needs_ordering) {
            return 'Reorder Soon';
        }

        return 'Stock Okay';
    }
}
