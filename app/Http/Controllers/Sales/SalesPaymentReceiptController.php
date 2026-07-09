<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesPayment;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesPaymentReceiptController extends Controller
{
    public function __invoke(SalesPayment $salesPayment)
    {
        abort_unless(auth()->user()?->can('view sales payments'), 403);

        $salesPayment->load([
            'invoice.customer',
            'invoice.items.animal',
            'receivedBy',
            'verifiedBy',
            'customer',
        ]);

        $user = auth()->user();

        $pdf = Pdf::loadView('pdf.sales-payment-receipt', [
            'payment' => $salesPayment,
            'invoice' => $salesPayment->invoice,
            'customer' => $salesPayment->customer ?? $salesPayment->invoice?->customer,
            'generatedBy' => $user,
            'generatedByRole' => $user?->getRoleNames()?->first() ?? 'User',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('receipt-' . $salesPayment->payment_number . '.pdf');
    }
}
