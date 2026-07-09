<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sales\MpesaC2BTransaction;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaC2BController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | VALIDATION URL
    |--------------------------------------------------------------------------
    */
    public function validation(Request $request)
    {
        Log::info('C2B VALIDATION RECEIVED', $request->all());

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRMATION URL
    |--------------------------------------------------------------------------
    */
    public function confirmation(Request $request)
    {
        Log::info('C2B CONFIRMATION RECEIVED', $request->all());

        $data = $request->all();

        /*
        |--------------------------------------------------------------------------
        | EXTRACT DATA
        |--------------------------------------------------------------------------
        */
        $receipt = strtoupper(trim($data['TransID'] ?? ''));
        $billRef = trim($data['BillRefNumber'] ?? '');
        $amount = (float) ($data['TransAmount'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | FIND INVOICE USING BILL REF
        |--------------------------------------------------------------------------
        */
        $invoice = SalesInvoice::query()
            ->where('invoice_number', $billRef)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | SAVE C2B TRANSACTION
        |--------------------------------------------------------------------------
        */
        $c2b = MpesaC2BTransaction::updateOrCreate(
            [
                'trans_id' => $receipt,
            ],
            [
                'sales_invoice_id' => $invoice?->id,
                'customer_id' => $invoice?->customer_id,

                'transaction_type' => $data['TransactionType'] ?? null,
                'trans_time' => $data['TransTime'] ?? null,
                'trans_amount' => $amount,

                'business_short_code' => $data['BusinessShortCode'] ?? null,

                'bill_ref_number' => $billRef,
                'invoice_number' => $billRef,

                'org_account_balance' => $data['OrgAccountBalance'] ?? null,
                'third_party_trans_id' => $data['ThirdPartyTransID'] ?? null,

                'phone_number' => $data['MSISDN'] ?? null,

                'first_name' => $data['FirstName'] ?? null,
                'middle_name' => $data['MiddleName'] ?? null,
                'last_name' => $data['LastName'] ?? null,

                'status' => $invoice ? 'matched' : 'unmatched',

                'payload' => $data,

                'received_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AUTO CREATE PAYMENT IF INVOICE FOUND
        |--------------------------------------------------------------------------
        */
        if ($invoice) {

            $existingPayment = SalesPayment::query()
                ->where('mpesa_receipt_number', $receipt)
                ->first();

            if (! $existingPayment) {

                $payment = SalesPayment::create([
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,

                    'payment_date' => now('Africa/Nairobi')->toDateString(),

                    'payment_method' => 'mpesa_paybill',

                    'status' => 'successful',

                    'amount' => $amount,

                    'reference_number' => $receipt,
                    'mpesa_receipt_number' => $receipt,

                    'paid_by_name' => trim(
                        ($data['FirstName'] ?? '') . ' ' .
                        ($data['MiddleName'] ?? '') . ' ' .
                        ($data['LastName'] ?? '')
                    ),

                    'paid_by_phone' => $data['MSISDN'] ?? null,

                    'notes' => 'Auto-created from Safaricom C2B confirmation.',

                    'verified_at' => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | LINK C2B TO PAYMENT
                |--------------------------------------------------------------------------
                */
                $c2b->update([
                    'sales_payment_id' => $payment->id,
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | UPDATE INVOICE TOTALS
                |--------------------------------------------------------------------------
                */
                $invoice->syncPaymentTotals();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE TO SAFARICOM
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Confirmation Received Successfully',
        ]);
    }
}
