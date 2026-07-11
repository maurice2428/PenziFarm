<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalesPaymentReceiptController extends Controller
{
    public function __invoke(
        Request $request,
        SalesPayment $salesPayment
    ): Response {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Authentication is required.'
        );

        $isAdministrator =
            method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole([
                'Administrator',
                'Admin',
            ]);

        $isAllowed =
            $isAdministrator
            || $user->can('print sales payments')
            || $user->can('view sales payments');

        abort_unless(
            $isAllowed,
            403,
            'You do not have permission to print sales receipts.'
        );

        $salesPayment->loadMissing([
            'invoice.customer',
            'invoice.items.animal.breed',
            'customer',
            'receivedBy',
            'verifiedBy',
        ]);

        $invoice = $salesPayment->invoice;

        $customer =
            $salesPayment->customer
            ?: $invoice?->customer;

        $generatedByRole =
            method_exists($user, 'getRoleNames')
                ? (
                    $user->getRoleNames()->first()
                    ?: 'User'
                )
                : 'User';

        $filename = sprintf(
            '%s.pdf',
            preg_replace(
                '/[^A-Za-z0-9._-]/',
                '-',
                $salesPayment->payment_number
                    ?: 'sales-payment-receipt'
            )
        );

        $pdf = Pdf::loadView(
            'pdf.sales-payment-receipt',
            [
                'payment' => $salesPayment,
                'invoice' => $invoice,
                'customer' => $customer,
                'generatedBy' => $user,
                'generatedByRole' => $generatedByRole,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
                'dpi' => 120,
                'fontHeightRatio' => 1.0,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultMediaType' => 'print',
            ]);

        return $pdf->stream($filename);
    }
}
