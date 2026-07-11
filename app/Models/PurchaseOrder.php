<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_number',
        'invoice_number',
        'supplier_invoice_number',
        'supplier_id',
        'order_date',
        'invoice_date',
        'due_date',
        'expected_delivery_date',
        'status',
        'payment_status',
        'payment_method',
        'mpesa_reference',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'other_charges',
        'grand_total',
        'amount_paid',
        'balance_due',
        'notes',
        'created_by',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $record): void {
            if (auth()->check()) {
                $record->created_by = auth()->id();
            }

            $today = now('Africa/Nairobi')->format('Ymd');
            $nextNumber = (int) static::withTrashed()->max('id') + 1;
            $sequence = str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);

            if (blank($record->purchase_order_number)) {
                $record->purchase_order_number = 'PO' . $today . $sequence;
            }

            if (blank($record->invoice_number)) {
                $record->invoice_number = 'PINV' . $today . $sequence;
            }

            if (blank($record->order_date)) {
                $record->order_date = now('Africa/Nairobi')->toDateString();
            }

            if (blank($record->invoice_date)) {
                $record->invoice_date = now('Africa/Nairobi')->toDateString();
            }

            if (blank($record->status)) {
                $record->status = 'draft';
            }

            if (blank($record->payment_status)) {
                $record->payment_status = 'unpaid';
            }

            if (blank($record->approval_status)) {
                $record->approval_status = 'draft';
            }

            /*
             * |--------------------------------------------------------------------------
             * | Very important:
             * |--------------------------------------------------------------------------
             * | These fields are NOT NULL in your database.
             * | Filament readonly total fields can submit null during create,
             * | so we force safe defaults before the first insert.
             */
            $record->subtotal = (float) ($record->subtotal ?? 0);
            $record->tax_amount = (float) ($record->tax_amount ?? 0);
            $record->discount_amount = (float) ($record->discount_amount ?? 0);
            $record->other_charges = (float) ($record->other_charges ?? 0);
            $record->grand_total = (float) ($record->grand_total ?? 0);
            $record->amount_paid = (float) ($record->amount_paid ?? 0);
            $record->balance_due = (float) ($record->balance_due ?? $record->grand_total ?? 0);
        });

        static::saved(function (PurchaseOrder $record): void {
            $record->recalculateTotals();
            $record->syncPaymentTotals();
        });
    }

    public function items()
    {
        return $this->hasMany(\App\Models\PurchaseOrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(\App\Models\PurchaseOrderReceipt::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\PurchaseOrderPayment::class);
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal = 0;
        $itemTax = 0;
        $itemDiscounts = 0;

        foreach ($items as $item) {
            $quantity = (float) ($item->quantity_ordered ?? 0);
            $unitCost = (float) ($item->unit_cost ?? 0);
            $discount = (float) ($item->discount_amount ?? 0);
            $taxRate = (float) ($item->tax_rate ?? 0);

            $lineSubtotal = (float) ($item->line_subtotal ?? 0);

            if ($lineSubtotal <= 0 && $quantity > 0 && $unitCost > 0) {
                $lineSubtotal = $quantity * $unitCost;
            }

            $taxableAmount = max(0, $lineSubtotal - $discount);
            $lineTax = (float) ($item->tax_amount ?? 0);

            if ($lineTax <= 0 && $taxRate > 0) {
                $lineTax = $taxableAmount * ($taxRate / 100);
            }

            $subtotal += $lineSubtotal;
            $itemTax += $lineTax;
            $itemDiscounts += $discount;
        }

        $orderDiscount = (float) ($this->discount_amount ?? 0);
        $otherCharges = (float) ($this->other_charges ?? 0);

        $grandTotal = max(
            0,
            $subtotal + $itemTax + $otherCharges - $itemDiscounts - $orderDiscount
        );

        $this->forceFill([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($itemTax, 2),
            'discount_amount' => round($orderDiscount, 2),
            'other_charges' => round($otherCharges, 2),
            'grand_total' => round($grandTotal, 2),
        ])->saveQuietly();
    }

    public function syncPaymentTotals(): void
    {
        $paid = (float) $this
            ->payments()
            ->successful()
            ->whereNull('deleted_at')
            ->sum('amount');

        $grandTotal = (float) ($this->grand_total ?? 0);
        $balance = max(0, $grandTotal - $paid);

        $paymentStatus = match (true) {
            $paid <= 0 => 'unpaid',
            $paid < $grandTotal => 'partial',
            default => 'paid',
        };

        $this->forceFill([
            'amount_paid' => round($paid, 2),
            'balance_due' => round($balance, 2),
            'payment_status' => $paymentStatus,
        ])->saveQuietly();
    }

    public function hasSuccessfulPayments(): bool
    {
        return $this->payments()
            ->successful()
            ->whereNull('deleted_at')
            ->exists();
    }

    public function hasActiveReceipts(): bool
    {
        return $this->receipts()
            ->whereNotIn('status', ['reversed', 'cancelled'])
            ->whereNull('deleted_at')
            ->exists();
    }

    public function hasInventoryHistory(): bool
    {
        return $this->stockMovements()
            ->whereNull('deleted_at')
            ->exists();
    }

    public function canBeDeletedSafely(): bool
    {
        return in_array($this->status, ['draft', 'cancelled'], true)
            && ! $this->hasSuccessfulPayments()
            && ! $this->hasActiveReceipts()
            && ! $this->hasInventoryHistory();
    }

    public function canBeCancelledSafely(): bool
    {
        return ! in_array($this->status, ['received', 'cancelled'], true)
            && ! $this->hasSuccessfulPayments()
            && ! $this->hasActiveReceipts();
    }

    public function receiveStock(): ?PurchaseOrderReceipt
    {
        return app(
            \App\Services\Procurement\PurchaseReceivingService::class
        )->receiveAllRemaining($this);
    }
}

