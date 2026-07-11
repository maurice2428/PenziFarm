<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderReceiptItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_receipt_id',
        'purchase_order_item_id',
        'inventory_item_id',
        'ordered_quantity',
        'previously_received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'balance_quantity',
        'unit_cost',
        'line_total',
        'batch_number',
        'expiry_date',
        'rejection_reason',
        'rejection_disposition',
        'rejection_status',
        'rejection_reference',
        'rejection_resolved_at',
        'rejection_resolved_by',
        'notes',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:3',
        'previously_received_quantity' => 'decimal:3',
        'accepted_quantity' => 'decimal:3',
        'rejected_quantity' => 'decimal:3',
        'balance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
        'rejection_resolved_at' => 'datetime',
    ];

    public function receipt()
    {
        return $this->belongsTo(
            PurchaseOrderReceipt::class,
            'purchase_order_receipt_id'
        );
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function rejectionResolvedBy()
    {
        return $this->belongsTo(
            User::class,
            'rejection_resolved_by'
        );
    }

    public function getRejectionDispositionLabelAttribute(): string
    {
        return str($this->rejection_disposition ?: 'none')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getClosesOrderBalanceAttribute(): bool
    {
        return in_array(
            $this->rejection_disposition,
            [
                'supplier_credit_note',
                'supplier_refund',
                'accepted_short_delivery',
            ],
            true
        );
    }
}
