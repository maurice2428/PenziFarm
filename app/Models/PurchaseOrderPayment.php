<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PurchaseOrderPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'payment_number',
        'payment_date',
        'paid_at',
        'amount',
        'payment_method',
        'status',
        'mpesa_reference',
        'mpesa_phone',
        'mpesa_receipt_number',
        'mpesa_merchant_request_id',
        'mpesa_checkout_request_id',
        'mpesa_result_code',
        'mpesa_result_description',
        'mpesa_callback_payload',
        'bank_name',
        'bank_reference',
        'cheque_number',
        'notes',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'mpesa_callback_payload' => 'array',
        'reversed_at' => 'datetime',
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

            if (blank($record->paid_at)) {
                $record->paid_at = now('Africa/Nairobi');
            }

            if (blank($record->payment_date)) {
                $record->payment_date = Carbon::parse(
                    $record->paid_at,
                    'Africa/Nairobi'
                )->toDateString();
            }

            if (
                blank($record->mpesa_reference)
                && filled($record->mpesa_receipt_number)
            ) {
                $record->mpesa_reference =
                    $record->mpesa_receipt_number;
            }
        });

        static::saving(function (PurchaseOrderPayment $record): void {
            if (filled($record->paid_at)) {
                $record->payment_date = Carbon::parse(
                    $record->paid_at,
                    'Africa/Nairobi'
                )->toDateString();
            }

            if (
                blank($record->mpesa_reference)
                && filled($record->mpesa_receipt_number)
            ) {
                $record->mpesa_reference =
                    $record->mpesa_receipt_number;
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

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
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
        return $this->mpesa_receipt_number
            ?: ($this->mpesa_reference
                ?: ($this->bank_reference
                    ?: ($this->cheque_number ?: '-')));
    }

    public function getTransactionDateTimeAttribute(): ?Carbon
    {
        if ($this->paid_at) {
            return $this->paid_at;
        }

        if ($this->payment_date) {
            return Carbon::parse(
                $this->payment_date->format('Y-m-d'),
                'Africa/Nairobi'
            )->startOfDay();
        }

        return null;
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

    public function getCanBeReversedAttribute(): bool
    {
        return $this->is_successful
            && blank($this->reversed_at)
            && $this->status !== 'reversed';
    }

    public function getCanBeDeletedSafelyAttribute(): bool
    {
        return ! $this->is_successful
            && blank($this->reversed_at)
            && in_array(
                $this->status,
                ['pending', 'failed', 'cancelled'],
                true
            );
    }
}
