<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'sales_payment_id',
        'sales_invoice_id',
        'customer_id',
        'merchant_request_id',
        'checkout_request_id',
        'phone_number',
        'amount',
        'account_reference',
        'transaction_desc',
        'mpesa_receipt_number',
        'result_code',
        'result_desc',
        'status',
        'request_payload',
        'callback_payload',
        'requested_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'callback_payload' => 'array',
        'requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
