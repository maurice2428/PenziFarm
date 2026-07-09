<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderPayment;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentVoucherController extends Controller
{
    public function __invoke(PurchaseOrderPayment $purchaseOrderPayment)
    {
        abort_unless(
            auth()->user()?->can('view purchase order payments') ||
                auth()->user()?->can('print payment vouchers') ||
                auth()->user()?->hasRole('Admin') ||
                auth()->user()?->hasRole('Administrator'),
            403
        );

        $purchaseOrderPayment->load([
            'purchaseOrder.supplier',
            'purchaseOrder.payments',
        ]);

        $payment = $purchaseOrderPayment;
        $purchaseOrder = $payment->purchaseOrder;
        $supplier = $purchaseOrder?->supplier;

        $voucherNumber = $payment->payment_number ?: ('PV-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT));

        $validPaymentStatuses = [
            'successful',
            'completed',
            'paid',
            'approved',
        ];

        $paidBefore = $purchaseOrder
            ? (float) $purchaseOrder
                ->payments()
                ->where('id', '!=', $payment->id)
                ->whereIn('status', $validPaymentStatuses)
                ->sum('amount')
            : 0;

        $thisPaymentAmount = in_array($payment->status, $validPaymentStatuses, true)
            ? (float) $payment->amount
            : 0;

        $invoiceTotal = (float) ($purchaseOrder?->grand_total ?? 0);

        $balanceBefore = max(0, $invoiceTotal - $paidBefore);
        $balanceAfter = max(0, $invoiceTotal - $paidBefore - $thisPaymentAmount);

        $generatedBy = auth()->user();

        $generatedByRole = $generatedBy && method_exists($generatedBy, 'getRoleNames')
            ? ($generatedBy->getRoleNames()->first() ?: 'User')
            : 'User';

        $pdf = Pdf::loadView('pdfs.procurement.payment-voucher', [
            'payment' => $payment,
            'purchaseOrder' => $purchaseOrder,
            'supplier' => $supplier,
            'voucherNumber' => $voucherNumber,
            'paidBefore' => $paidBefore,
            'balanceBefore' => $balanceBefore,
            'balanceAfter' => $balanceAfter,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
        ])
            ->setPaper('a4')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->stream($voucherNumber . '.pdf');
    }
}
