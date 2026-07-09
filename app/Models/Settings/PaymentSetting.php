<?php

namespace App\Models\Settings;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'mpesa_environment',
        'mpesa_consumer_key',
        'mpesa_consumer_secret',
        'mpesa_shortcode',
        'mpesa_passkey',
        'mpesa_callback_url',
        'mpesa_account_reference_prefix',
        'mpesa_transaction_description',
        'enable_mpesa_stk',
        'enable_mpesa_paybill',
        'enable_bank_payment',
        'enable_cash_payment',
        'enable_cheque_payment',
        'mpesa_paybill_number',
        'mpesa_till_number',
        'mpesa_account_name',
        'mpesa_logo',
        'invoice_payment_instructions',
        'receipt_footer_note',
        'invoice_footer_note',
        'default_currency',
        'default_tax_rate',
        'prices_include_tax',
        'payment_stamp_image',
        'authorized_signature_image',
        'updated_by',
        'bank_name',
        'bank_branch',
        'bank_account_name',
        'bank_account_number',
        'bank_swift_code',
        'bank_paybill_number',
        'bank_account_reference',
        'bank_logo',
        'invoice_signature_path',
        'invoice_stamp_path',
    ];

    protected $casts = [
        'enable_mpesa_stk' => 'boolean',
        'enable_mpesa_paybill' => 'boolean',
        'enable_bank_payment' => 'boolean',
        'enable_cash_payment' => 'boolean',
        'enable_cheque_payment' => 'boolean',
        'prices_include_tax' => 'boolean',
        'default_tax_rate' => 'decimal:2',
        // Laravel encrypted casts
        'mpesa_consumer_secret' => 'encrypted',
        'mpesa_passkey' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'mpesa_environment' => 'sandbox',
                'mpesa_account_reference_prefix' => 'LLK',
                'mpesa_transaction_description' => 'Lelekwe Farm Payment',
                'enable_mpesa_stk' => false,
                'enable_mpesa_paybill' => true,
                'enable_bank_payment' => true,
                'enable_cash_payment' => true,
                'default_currency' => 'KES',
                'default_tax_rate' => 0,
                'prices_include_tax' => false,
            ]
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
