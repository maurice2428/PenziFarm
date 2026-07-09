<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesDashboardReportController extends Controller
{
    public function __invoke(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        abort_unless(
            auth()->user()?->can('view sales invoices')
                || auth()->user()?->can('view sales payments')
                || auth()->user()?->hasRole('Administrator'),
            403
        );

        $dateFrom = Carbon::parse(
            $request->query('date_from', now('Africa/Nairobi')->startOfYear()->toDateString())
        )->toDateString();

        $dateTo = Carbon::parse(
            $request->query('date_to', now('Africa/Nairobi')->toDateString())
        )->toDateString();

        $paymentStatus = $request->query('payment_status');
        $invoiceStatus = $request->query('invoice_status');
        $paymentMethod = $request->query('payment_method');
        $saleType = $request->query('sale_type');

        $invoiceQuery = SalesInvoice::query()
            ->with(['customer', 'items.animal'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($paymentStatus, fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($invoiceStatus, fn ($q) => $q->where('status', $invoiceStatus))
            ->when($saleType, fn ($q) => $q->where('sale_type', $saleType));

        $paymentQuery = SalesPayment::query()
            ->with(['invoice.customer', 'customer', 'receivedBy', 'verifiedBy'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($paymentStatus, function ($q) use ($paymentStatus) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('payment_status', $paymentStatus));
            })
            ->when($invoiceStatus, function ($q) use ($invoiceStatus) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('status', $invoiceStatus));
            });

        $invoices = $invoiceQuery
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $payments = $paymentQuery
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $successfulPayments = $payments->where('status', 'successful');

        $totalSales = (float) $invoices->sum('grand_total');
        $amountPaid = (float) $successfulPayments->sum('amount');
        $balanceDue = max(0, $totalSales - $amountPaid);
        $invoiceCount = $invoices->count();
        $paymentCount = $payments->count();

        $saleTypeSummary = $invoices
            ->groupBy('sale_type')
            ->map(fn ($items, $type) => [
                'type' => str($type)->replace('_', ' ')->title(),
                'count' => $items->count(),
                'total' => (float) $items->sum('grand_total'),
                'paid' => (float) $items->sum('amount_paid'),
                'balance' => (float) $items->sum('balance_due'),
            ])
            ->sortByDesc('total')
            ->values();

        $paymentMethodSummary = $successfulPayments
            ->groupBy('payment_method')
            ->map(fn ($items, $method) => [
                'method' => str($method)->replace('_', ' ')->title(),
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values();

        $topCustomers = $invoices
            ->groupBy('customer_id')
            ->map(function ($items) {
                $customer = $items->first()->customer;

                return [
                    'name' => $customer?->name ?? 'Unknown Customer',
                    'invoice_count' => $items->count(),
                    'total' => (float) $items->sum('grand_total'),
                    'paid' => (float) $items->sum('amount_paid'),
                    'balance' => (float) $items->sum('balance_due'),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();

        $collectionRate = $totalSales > 0
            ? round(($amountPaid / $totalSales) * 100, 1)
            : 0;

        $smartNotes = [];

        $smartNotes[] = "The report covers sales activity from {$dateFrom} to {$dateTo}.";

        if ($totalSales > 0) {
            $smartNotes[] = "Total invoice value is KES " . number_format($totalSales, 2) . ", with confirmed collections of KES " . number_format($amountPaid, 2) . ".";
        } else {
            $smartNotes[] = "No invoice revenue was recorded within the selected reporting period.";
        }

        if ($balanceDue > 0) {
            $smartNotes[] = "Outstanding balances currently stand at KES " . number_format($balanceDue, 2) . ". Customer follow-up is recommended.";
        } else {
            $smartNotes[] = "All invoice balances in the selected period are fully cleared.";
        }

        if ($collectionRate < 50 && $totalSales > 0) {
            $smartNotes[] = "Collection rate is below 50%. Consider tightening payment follow-up and confirming pending transactions.";
        } elseif ($collectionRate >= 80) {
            $smartNotes[] = "Collection performance is strong, with a collection rate of {$collectionRate}%.";
        }

        $dominantPaymentMethod = $paymentMethodSummary->first();

        if ($dominantPaymentMethod) {
            $smartNotes[] = $dominantPaymentMethod['method'] . " is the leading payment method, contributing KES " . number_format($dominantPaymentMethod['total'], 2) . ".";
        }

        $dominantSaleType = $saleTypeSummary->first();

        if ($dominantSaleType) {
            $smartNotes[] = $dominantSaleType['type'] . " is the highest performing sale type by value.";
        }

        $suggestions = [];

        if ($balanceDue > 0) {
            $suggestions[] = "Follow up on unpaid and partially paid invoices.";
        }

        if ($payments->where('status', 'pending')->count() > 0) {
            $suggestions[] = "Review pending payments and verify M-Pesa transaction codes where applicable.";
        }

        if ($saleTypeSummary->count() > 1) {
            $suggestions[] = "Compare sale type performance to identify the strongest revenue streams.";
        }

        if ($paymentMethodSummary->where('method', 'Cash')->sum('total') > 0) {
            $suggestions[] = "Encourage digital payment channels for better reconciliation and audit trails.";
        }

        if (empty($suggestions)) {
            $suggestions[] = "Sales and collections appear stable for the selected period.";
        }

        $pdf = Pdf::loadView('pdfs.sales.sales-dashboard-report', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => [
                'payment_status' => $paymentStatus,
                'invoice_status' => $invoiceStatus,
                'payment_method' => $paymentMethod,
                'sale_type' => $saleType,
            ],
            'invoices' => $invoices,
            'payments' => $payments,
            'saleTypeSummary' => $saleTypeSummary,
            'paymentMethodSummary' => $paymentMethodSummary,
            'topCustomers' => $topCustomers,
            'smartNotes' => $smartNotes,
            'suggestions' => $suggestions,
            'totalSales' => $totalSales,
            'amountPaid' => $amountPaid,
            'balanceDue' => $balanceDue,
            'successfulPayments' => (float) $successfulPayments->sum('amount'),
            'invoiceCount' => $invoiceCount,
            'paymentCount' => $paymentCount,
            'collectionRate' => $collectionRate,
            'generatedBy' => auth()->user(),
            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'sales-dashboard-report-' . now('Africa/Nairobi')->format('Ymd_His') . '.pdf'
        );
    }
}
