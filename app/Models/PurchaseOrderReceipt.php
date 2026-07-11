<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_no',
        'purchase_order_id',
        'received_date',
        'delivery_note_no',
        'supplier_invoice_no',
        'status',
        'total_accepted_quantity',
        'total_rejected_quantity',
        'total_received_value',
        'notes',
        'received_by',
        'created_by',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'received_date' => 'date',
        'total_accepted_quantity' => 'decimal:3',
        'total_rejected_quantity' => 'decimal:3',
        'total_received_value' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrderReceipt $receipt): void {
            if (blank($receipt->receipt_no)) {
                $receipt->receipt_no =
                    'GRN'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad(
                        (string) (
                            (int) static::withTrashed()->max('id') + 1
                        ),
                        5,
                        '0',
                        STR_PAD_LEFT
                    );
            }

            if (auth()->check()) {
                $receipt->created_by ??= auth()->id();
                $receipt->received_by ??= auth()->id();
            }

            $receipt->received_date ??=
                now('Africa/Nairobi')->toDateString();
        });
    }

    public function purchaseOrder()
    {
        /*
         * A GRN remains part of the audit trail even when its purchase
         * order has been archived. Include soft-deleted purchase orders
         * so reversal and reconciliation can still resolve the source.
         */
        return $this->belongsTo(
            PurchaseOrder::class
        )->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderReceiptItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(
            StockMovement::class,
            'referenceable'
        );
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function getIsReversedAttribute(): bool
    {
        return $this->status === 'reversed'
            || filled($this->reversed_at);
    }

    public function getCanBeReversedAttribute(): bool
    {
        return ! $this->is_reversed
            && in_array($this->status, ['received', 'partial'], true);
    }

    public function getCanBeDeletedSafelyAttribute(): bool
    {
        return $this->is_reversed;
    }
}
