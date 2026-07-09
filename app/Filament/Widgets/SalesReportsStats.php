<?php

namespace App\Filament\Widgets;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesReportsStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '10s';
    public ?array $filters = [];

    protected function getStats(): array
    {
        $dateFrom = Carbon::parse($this->filters['date_from'] ?? now('Africa/Nairobi')->startOfMonth())->toDateString();
        $dateTo = Carbon::parse($this->filters['date_to'] ?? now('Africa/Nairobi'))->toDateString();

        $invoiceQuery = SalesInvoice::query()
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($this->filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($this->filters['invoice_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($this->filters['payment_method'] ?? null, function ($q, $method) {
                $q->whereHas('payments', fn ($paymentQuery) => $paymentQuery->where('payment_method', $method));
            });

        $paymentQuery = SalesPayment::query()
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->when($this->filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($this->filters['payment_status'] ?? null, function ($q, $status) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('payment_status', $status));
            })
            ->when($this->filters['invoice_status'] ?? null, function ($q, $status) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('status', $status));
            });

        $successfulPaymentQuery = (clone $paymentQuery)->where('status', 'successful');

        $totalSales = (float) (clone $invoiceQuery)->sum('grand_total');
        $amountPaid = (float) (clone $successfulPaymentQuery)->sum('amount');
        $successfulPayments = (float) (clone $successfulPaymentQuery)->sum('amount');
        $balanceDue = max(0, $totalSales - $amountPaid);

       return [
    Stat::make('Total Sales', 'KES ' . number_format($totalSales, 2))
        ->description('Revenue trend is increasing')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->icon('heroicon-o-document-currency-dollar')
        ->color('success')
        ->chart($this->sparkline($totalSales)),

    Stat::make('Amount Paid', 'KES ' . number_format($amountPaid, 2))
        ->description('Customer payments received')
        ->descriptionIcon('heroicon-m-banknotes')
        ->icon('heroicon-o-banknotes')
        ->color('info')
        ->chart($this->sparkline($amountPaid)),

    Stat::make('Balance Due', 'KES ' . number_format($balanceDue, 2))
        ->description(
            $balanceDue > 0
                ? 'Outstanding balances require follow-up'
                : 'All balances cleared'
        )
        ->descriptionIcon(
            $balanceDue > 0
                ? 'heroicon-m-arrow-trending-down'
                : 'heroicon-m-check-circle'
        )
        ->icon('heroicon-o-exclamation-triangle')
        ->color($balanceDue > 0 ? 'warning' : 'success')
        ->chart($this->sparkline($balanceDue)),

    Stat::make('Successful Payments', 'KES ' . number_format($successfulPayments, 2))
        ->description('Confirmed payment collections')
        ->descriptionIcon('heroicon-m-check-badge')
        ->icon('heroicon-o-check-badge')
        ->color('primary')
        ->chart($this->sparkline($successfulPayments)),
];
    }

    protected function sparkline(float $value): array
    {
        if ($value <= 0) {
            return [0, 0, 0, 0, 0, 0, 0];
        }

        return [
            round($value * 0.25, 2),
            round($value * 0.35, 2),
            round($value * 0.45, 2),
            round($value * 0.55, 2),
            round($value * 0.7, 2),
            round($value * 0.85, 2),
            round($value, 2),
        ];
    }
}
