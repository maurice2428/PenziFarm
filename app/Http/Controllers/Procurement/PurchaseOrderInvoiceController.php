<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderInvoiceController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        abort_unless(auth()->user()?->can('view purchase orders'), 403);

        $purchaseOrder->load([
            'supplier',
            'items.healthProduct',
            'items.inventoryItem',
            'payments',
            'movements',
        ]);

        $generatedBy = auth()->user();
        $generatedByRole = $generatedBy?->getRoleNames()?->first() ?? 'User';

        $pdf = Pdf::loadView('pdfs.procurement.purchase-order-invoice', [
            'purchaseOrder' => $purchaseOrder,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
                'defaultFont' => 'Courier',
                'enable_php' => true,
            ]);

        return $pdf->stream(
            ($purchaseOrder->invoice_number ?: $purchaseOrder->purchase_order_number) . '.pdf'
        );
    }
}
