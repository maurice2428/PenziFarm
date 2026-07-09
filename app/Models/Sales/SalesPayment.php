<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id',
        'customer_id',
        'payment_date',
        'payment_method',
        'status',
        'amount',
        'reference_number',
        'mpesa_receipt_number',
        'bank_name',
        'paid_by_name',
        'paid_by_phone',
        'notes',
        'received_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesPayment $payment) {
            if (blank($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }

            if (blank($payment->payment_date)) {
                $payment->payment_date = now('Africa/Nairobi')->toDateString();
            }
            if ($payment->payment_method === 'mpesa_stk') {
                $payment->status = 'pending';
            }

            if (auth()->check()) {
                $payment->received_by = auth()->id();

                if ($payment->status === 'successful') {
                    $payment->verified_by = auth()->id();
                    $payment->verified_at = now();
                }
            }

            if ($payment->sales_invoice_id && blank($payment->customer_id)) {
                $payment->customer_id = SalesInvoice::find($payment->sales_invoice_id)?->customer_id;
            }
        });

        static::saved(function (SalesPayment $payment) {
            $payment->invoice?->syncPaymentTotals();
        });

        static::deleted(function (SalesPayment $payment) {
            $payment->invoice?->syncPaymentTotals();
        });

        static::restored(function (SalesPayment $payment) {
            $payment->invoice?->syncPaymentTotals();
        });
    }

    public static function generatePaymentNumber(): string
    {
        $year = now('Africa/Nairobi')->format('Y');
        $latestId = static::withTrashed()->max('id') ?? 0;

        return 'PAY' . $year . str_pad($latestId + 1, 5, '0', STR_PAD_LEFT);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return str($this->payment_method)->replace('_', ' ')->title();
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title();
    }

    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class, 'sales_payment_id');
    }
}
