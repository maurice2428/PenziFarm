<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaC2BTransaction extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'sales_payment_id',
        'customer_id',
        'transaction_type',
        'trans_id',
        'trans_time',
        'trans_amount',
        'business_short_code',
        'bill_ref_number',
        'invoice_number',
        'org_account_balance',
        'third_party_trans_id',
        'phone_number',
        'first_name',
        'middle_name',
        'last_name',
        'status',
        'payload',
        'received_at',
        'verified_at',
    ];

    protected $casts = [
        'trans_amount' => 'decimal:2',
        'org_account_balance' => 'decimal:2',
        'payload' => 'array',
        'received_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
