<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sales\MpesaTransaction;
use App\Models\Sales\SalesPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function stkCallback(Request $request)
    {
        Log::info('MPESA CALLBACK RECEIVED', $request->all());

        $callback = $request->input('Body.stkCallback', []);

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = (string) ($callback['ResultCode'] ?? '');
        $resultDesc = $callback['ResultDesc'] ?? null;

        if (! $checkoutRequestId) {
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }

        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $transaction) {
            Log::warning('MPESA CALLBACK TRANSACTION NOT FOUND', [
                'checkout_request_id' => $checkoutRequestId,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }

        $metadata = collect($callback['CallbackMetadata']['Item'] ?? [])
            ->mapWithKeys(fn ($item) => [
                $item['Name'] ?? '' => $item['Value'] ?? null,
            ]);

        $receipt = $metadata->get('MpesaReceiptNumber');

        $status = match ($resultCode) {
            '0' => 'successful',
            '1032' => 'cancelled',
            default => 'failed',
        };

        $transaction->update([
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'mpesa_receipt_number' => $status === 'successful' ? $receipt : null,
            'status' => $status,
            'callback_payload' => [
                'full_callback' => $request->all(),
                'metadata' => $metadata->toArray(),
                'receipt' => $receipt,
            ],
            'paid_at' => $status === 'successful' ? now() : null,
        ]);

        $salesPayment = SalesPayment::find($transaction->sales_payment_id);

        if ($salesPayment) {
            $salesPayment->update([
                'status' => $status,
                'mpesa_receipt_number' => $status === 'successful' ? $receipt : null,
                'reference_number' => $status === 'successful' ? $receipt : $salesPayment->reference_number,
                'verified_by' => $status === 'successful' ? $salesPayment->received_by : null,
                'verified_at' => $status === 'successful' ? now() : null,
                'notes' => trim(($salesPayment->notes ?? '') . "\nM-Pesa: " . $resultDesc),
            ]);

            $salesPayment->invoice?->syncPaymentTotals();
        }

        Log::info('MPESA CALLBACK PROCESSED', [
            'checkout_request_id' => $checkoutRequestId,
            'status' => $status,
            'receipt' => $receipt,
        ]);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
