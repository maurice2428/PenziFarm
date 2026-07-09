<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SupplierStatementController extends Controller
{
    public function __invoke(Request $request, Supplier $supplier)
    {
        abort_unless(
            auth()->user()?->can('view suppliers')
            || auth()->user()?->can('view purchase orders')
            || auth()->user()?->can('print supplier statements')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator'),
            403
        );

        $from = $request->query('from');
        $to = $request->query('to');

        $purchaseOrders = PurchaseOrder::query()
            ->where('supplier_id', $supplier->id)
            ->when($from, function ($query) use ($from) {
                $query->whereDate('order_date', '>=', $from);
            })
            ->when($to, function ($query) use ($to) {
                $query->whereDate('order_date', '<=', $to);
            })
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $payments = PurchaseOrderPayment::query()
            ->whereHas('purchaseOrder', function ($query) use ($supplier) {
                $query->where('supplier_id', $supplier->id);
            })
            ->with('purchaseOrder')
            ->when($from, function ($query) use ($from) {
                $query->whereDate('payment_date', '>=', $from);
            })
            ->when($to, function ($query) use ($to) {
                $query->whereDate('payment_date', '<=', $to);
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $generatedBy = auth()->user();

        $generatedByRole = $generatedBy && method_exists($generatedBy, 'getRoleNames')
            ? ($generatedBy->getRoleNames()->first() ?: 'User')
            : 'User';

        $supplierName = $supplier->company_name
            ?? $supplier->name
            ?? ('Supplier-' . $supplier->id);

        $safeSupplierName = str($supplierName)
            ->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-')
            ->toString();

        $pdf = Pdf::loadView('pdfs.procurement.supplier-statement', [
            'supplier' => $supplier,
            'purchaseOrders' => $purchaseOrders,
            'payments' => $payments,
            'from' => $from,
            'to' => $to,
            'generatedBy' => $generatedBy,
            'generatedByRole' => $generatedByRole,
        ])
            ->setPaper('a4')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->stream('Supplier-Statement-' . $safeSupplierName . '.pdf');
    }
}
