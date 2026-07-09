<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'status',
        'mpesa_reference',
        'bank_name',
        'bank_reference',
        'cheque_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrderPayment $record): void {
            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }

            if (blank($record->payment_number)) {
                $record->payment_number = static::generatePaymentNumber();
            }

            if (blank($record->status)) {
                $record->status = 'successful';
            }

            if (blank($record->payment_date)) {
                $record->payment_date = now('Africa/Nairobi')->toDateString();
            }
        });

        static::saved(function (PurchaseOrderPayment $record): void {
            $record->purchaseOrder?->syncPaymentTotals();
        });

        static::deleted(function (PurchaseOrderPayment $record): void {
            $record->purchaseOrder?->syncPaymentTotals();
        });

        static::restored(function (PurchaseOrderPayment $record): void {
            $record->purchaseOrder?->syncPaymentTotals();
        });

        static::forceDeleted(function (PurchaseOrderPayment $record): void {
            $record->purchaseOrder?->syncPaymentTotals();
        });
    }

    public static function generatePaymentNumber(): string
    {
        $nextId = (int) static::withTrashed()->max('id') + 1;

        return 'POPAY'
            . now('Africa/Nairobi')->format('Ymd')
            . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereIn('status', [
            'successful',
            'paid',
            'approved',
            'completed',
        ]);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return str($this->payment_method ?: '-')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getPaymentReferenceAttribute(): string
    {
        return $this->mpesa_reference
            ?: ($this->bank_reference
                ?: ($this->cheque_number
                    ?: '-'));
    }

    public function getVoucherNumberAttribute(): string
    {
        return $this->payment_number
            ?: 'PV'
                . now('Africa/Nairobi')->format('Ymd')
                . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'successful')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'successful', 'paid', 'approved', 'completed' => 'success',
            'pending', 'processing' => 'warning',
            'failed', 'cancelled', 'reversed' => 'danger',
            default => 'gray',
        };
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return in_array($this->status, [
            'successful',
            'paid',
            'approved',
            'completed',
        ], true);
    }
}
