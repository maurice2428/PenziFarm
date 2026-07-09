<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'health_product_id',
        'inventory_item_id',

        'quantity_ordered',
        'quantity_received',
        'received_quantity',
        'rejected_quantity',
        'receiving_status',

        'unit_cost',
        'line_subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',

        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'rejected_quantity' => 'decimal:3',

        'unit_cost' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',

        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item): void {
            $quantity = (float) ($item->quantity_ordered ?? 0);
            $unitCost = (float) ($item->unit_cost ?? 0);
            $discount = (float) ($item->discount_amount ?? 0);
            $taxRate = (float) ($item->tax_rate ?? 0);

            $subtotal = $quantity * $unitCost;
            $taxableAmount = max(0, $subtotal - $discount);
            $tax = $taxableAmount * ($taxRate / 100);

            $item->line_subtotal = $subtotal;
            $item->tax_amount = $tax;
            $item->line_total = $taxableAmount + $tax;

            if (blank($item->quantity_received)) {
                $item->quantity_received = 0;
            }

            if (blank($item->received_quantity)) {
                $item->received_quantity = 0;
            }

            if (blank($item->rejected_quantity)) {
                $item->rejected_quantity = 0;
            }

            if (blank($item->receiving_status)) {
                $item->receiving_status = 'pending';
            }
        });

        static::saved(function (PurchaseOrderItem $item): void {
            $item->purchaseOrder?->recalculateTotals();
            $item->purchaseOrder?->syncPaymentTotals();
        });

        static::deleted(function (PurchaseOrderItem $item): void {
            $item->purchaseOrder?->recalculateTotals();
            $item->purchaseOrder?->syncPaymentTotals();
        });

        static::restored(function (PurchaseOrderItem $item): void {
            $item->purchaseOrder?->recalculateTotals();
            $item->purchaseOrder?->syncPaymentTotals();
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function healthProduct()
    {
        return $this->belongsTo(HealthProduct::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function receiptItems()
    {
        return $this->hasMany(PurchaseOrderReceiptItem::class);
    }

    public function getAcceptedQuantityAttribute(): float
    {
        return (float) $this->receiptItems()
            ->whereNull('deleted_at')
            ->sum('accepted_quantity');
    }

    public function getRejectedQuantityTotalAttribute(): float
    {
        return (float) $this->receiptItems()
            ->whereNull('deleted_at')
            ->sum('rejected_quantity');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->accepted_quantity);
    }

    public function getReceivingStatusLabelAttribute(): string
    {
        return str($this->receiving_status ?: 'pending')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
