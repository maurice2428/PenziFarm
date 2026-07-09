<?php

namespace App\Services\Mpesa;

use App\Models\Sales\MpesaTransaction;
use App\Models\Sales\SalesPayment;
use App\Models\Settings\PaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MpesaDarajaService
{
    public function sendStkPush(SalesPayment $payment): MpesaTransaction
    {
        $settings = PaymentSetting::current();

        if (! $settings->enable_mpesa_stk) {
            throw new RuntimeException('M-Pesa STK Push is disabled in Payment Settings.');
        }

        if (! $settings->mpesa_consumer_key || ! $settings->mpesa_consumer_secret || ! $settings->mpesa_shortcode || ! $settings->mpesa_passkey) {
            throw new RuntimeException('M-Pesa credentials are incomplete.');
        }

        $invoice = $payment->invoice;
        $phone = $this->normalizePhone($payment->paid_by_phone);

        $amount = (int) round((float) $payment->amount);

        if ($amount <= 0) {
            throw new RuntimeException('M-Pesa amount must be at least KES 1.');
        }

        $timestamp = now('Africa/Nairobi')->format('YmdHis');
        $password = base64_encode($settings->mpesa_shortcode . $settings->mpesa_passkey . $timestamp);

        $baseUrl = $settings->mpesa_environment === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $token = $this->accessToken($settings, $baseUrl);

        $accountReference = $invoice?->invoice_number ?? $payment->payment_number;
        $description = $settings->mpesa_transaction_description ?: 'Lelekwe Farm Payment';

        $payload = [
            'BusinessShortCode' => $settings->mpesa_shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $settings->mpesa_shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $settings->mpesa_callback_url,
            'AccountReference' => Str::limit($accountReference, 12, ''),
            'TransactionDesc' => Str::limit($description, 13, ''),
        ];

        $response = Http::withToken($token)
            ->timeout(45)
            ->post($baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

        $data = $response->json();

        if (! $response->successful()) {
            throw new RuntimeException($data['errorMessage'] ?? 'STK Push request failed.');
        }

        return MpesaTransaction::create([
            'sales_payment_id' => $payment->id,
            'sales_invoice_id' => $payment->sales_invoice_id,
            'customer_id' => $payment->customer_id,
            'merchant_request_id' => $data['MerchantRequestID'] ?? null,
            'checkout_request_id' => $data['CheckoutRequestID'] ?? null,
            'phone_number' => $phone,
            'amount' => $payment->amount,
            'account_reference' => $accountReference,
            'transaction_desc' => $description,
            'status' => 'pending',
            'request_payload' => [
                'request' => $payload,
                'response' => $data,
            ],
            'requested_at' => now(),
        ]);
    }

    protected function accessToken(PaymentSetting $settings, string $baseUrl): string
    {
        $basic = base64_encode($settings->mpesa_consumer_key . ':' . $settings->mpesa_consumer_secret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $basic,
        ])->timeout(30)
            ->get($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        if (! $response->successful()) {
            throw new RuntimeException('Could not generate M-Pesa access token.');
        }

        return $response->json('access_token');
    }

    protected function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        }

        if (! str_starts_with($phone, '254')) {
            throw new RuntimeException('Phone number must be a valid Kenyan number.');
        }

        return $phone;
    }
}
