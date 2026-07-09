<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'movement_no',
        'inventory_item_id',
        'direction',
        'type',
        'source',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'movement_date',
        'referenceable_type',
        'referenceable_id',
        'purchase_order_id',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'movement_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement): void {
            if (blank($movement->movement_no)) {
                $movement->movement_no =
                    'STM' .
                    now('Africa/Nairobi')->format('Ymd') .
                    str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (blank($movement->source)) {
                $movement->source = $movement->type ?: 'manual';
            }

            if (blank($movement->type)) {
                $movement->type = $movement->source ?: 'manual';
            }

            if (blank($movement->direction)) {
                $movement->direction = ((float) $movement->quantity < 0) ? 'out' : 'in';
            }

            $movement->quantity = abs((float) $movement->quantity);

            if (blank($movement->movement_date)) {
                $movement->movement_date = now('Africa/Nairobi')->toDateString();
            }

            $movement->total_cost = abs((float) $movement->quantity) * (float) $movement->unit_cost;

            if (auth()->check() && blank($movement->created_by)) {
                $movement->created_by = auth()->id();
            }
        });
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function referenceable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMovementNoDisplayAttribute(): string
    {
        return filled($this->movement_no) ? $this->movement_no : 'N/A';
    }

    public function getDirectionLabelAttribute(): string
    {
        return match ($this->direction) {
            'in' => 'Stock In',
            'out' => 'Stock Out',
            'adjustment' => 'Adjustment',
            default => str($this->direction ?: 'unknown')->replace('_', ' ')->title()->toString(),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type ?: $this->source ?: 'manual')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getSourceLabelAttribute(): string
    {
        return str($this->source ?: $this->type ?: 'system')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getSignedQuantityAttribute(): string
    {
        $rawQuantity = (float) $this->quantity;
        $quantity = abs($rawQuantity);

        $direction = $this->direction;

        if (blank($direction)) {
            $direction = $rawQuantity < 0 ? 'out' : 'in';
        }

        $prefix = match ($direction) {
            'out' => '-',
            'in' => '+',
            default => '',
        };

        return $prefix . number_format($quantity, 3) . ' ' . ($this->unit ?: '');
    }

    public function getReferenceLabelAttribute(): string
    {
        if ($this->purchaseOrder) {
            return $this->purchaseOrder->purchase_order_number
                ?? $this->purchaseOrder->invoice_number
                ?? 'Purchase Order';
        }

        if ($this->referenceable) {
            foreach (['receipt_no', 'feeding_no', 'payment_number', 'reference_no', 'number'] as $field) {
                if (filled($this->referenceable->{$field} ?? null)) {
                    return $this->referenceable->{$field};
                }
            }

            return class_basename($this->referenceable);
        }

        return 'N/A';
    }

    public function getItemNameDisplayAttribute(): string
    {
        return $this->inventoryItem?->name ?: 'N/A';
    }

    public function getBatchDisplayAttribute(): string
    {
        return filled($this->batch_number) ? $this->batch_number : 'N/A';
    }

    public function getNotesDisplayAttribute(): string
    {
        return filled($this->notes) ? $this->notes : 'N/A';
    }
}
