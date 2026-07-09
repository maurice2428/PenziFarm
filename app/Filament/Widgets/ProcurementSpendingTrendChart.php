<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ProcurementSpendingTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Procurement Financial Movement';

    protected static ?string $description = 'Comparative trend of purchase value, supplier payments, unpaid balances, and item tax exposure.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function getBuckets(): array
    {
        $from = Carbon::parse($this->dateFrom ?: now('Africa/Nairobi')->startOfMonth())->startOfDay();
        $to = Carbon::parse($this->dateTo ?: now('Africa/Nairobi')->endOfMonth())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $days = $from->diffInDays($to);
        $buckets = [];

        if ($days <= 45) {
            $cursor = $from->copy();

            while ($cursor->lte($to)) {
                $buckets[] = [
                    'label' => $cursor->format('d M'),
                    'from' => $cursor->copy()->toDateString(),
                    'to' => $cursor->copy()->toDateString(),
                ];

                $cursor->addDay();
            }

            return $buckets;
        }

        if ($days <= 180) {
            $cursor = $from->copy()->startOfWeek();

            while ($cursor->lte($to)) {
                $start = $cursor->copy()->max($from);
                $end = $cursor->copy()->endOfWeek()->min($to);

                $buckets[] = [
                    'label' => $start->format('d M') . ' - ' . $end->format('d M'),
                    'from' => $start->toDateString(),
                    'to' => $end->toDateString(),
                ];

                $cursor->addWeek();
            }

            return $buckets;
        }

        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $start = $cursor->copy()->max($from);
            $end = $cursor->copy()->endOfMonth()->min($to);

            $buckets[] = [
                'label' => $cursor->format('M Y'),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ];

            $cursor->addMonth();
        }

        return $buckets;
    }

    protected function getData(): array
    {
        $labels = [];
        $purchaseTotals = [];
        $paymentTotals = [];
        $balanceTotals = [];
        $taxTotals = [];

        foreach ($this->getBuckets() as $bucket) {
            $labels[] = $bucket['label'];

            $purchaseTotals[] = round((float) PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$bucket['from'], $bucket['to']])
                ->sum('grand_total'), 2);

            $paymentTotals[] = round((float) PurchaseOrderPayment::query()
                ->whereNull('deleted_at')
                ->where('status', 'successful')
                ->whereBetween('payment_date', [$bucket['from'], $bucket['to']])
                ->sum('amount'), 2);

            $balanceTotals[] = round((float) PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$bucket['from'], $bucket['to']])
                ->sum('balance_due'), 2);

            $taxTotals[] = round((float) PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$bucket['from'], $bucket['to']])
                ->sum('tax_amount'), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Purchase Orders',
                    'data' => $purchaseTotals,
                    'borderColor' => '#2dd4bf',
                    'backgroundColor' => 'rgba(45, 212, 191, .14)',
                    'pointBackgroundColor' => '#2dd4bf',
                    'pointBorderColor' => '#ffffff',
                    'borderWidth' => 4,
                    'tension' => 0.45,
                    'fill' => true,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                ],
                [
                    'label' => 'Supplier Payments',
                    'data' => $paymentTotals,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, .12)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#ffffff',
                    'borderWidth' => 4,
                    'tension' => 0.45,
                    'fill' => false,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                ],
                [
                    'label' => 'Outstanding Balances',
                    'data' => $balanceTotals,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, .10)',
                    'pointBackgroundColor' => '#3b82f6',
                    'pointBorderColor' => '#ffffff',
                    'borderWidth' => 4,
                    'tension' => 0.45,
                    'fill' => false,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                ],
                [
                    'label' => 'Item Tax',
                    'data' => $taxTotals,
                    'borderColor' => '#a855f7',
                    'backgroundColor' => 'rgba(168, 85, 247, .10)',
                    'pointBackgroundColor' => '#a855f7',
                    'pointBorderColor' => '#ffffff',
                    'borderWidth' => 3,
                    'tension' => 0.45,
                    'fill' => false,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 18,
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(15, 23, 42, .95)',
                    titleFont: { weight: 'bold' },
                    bodySpacing: 6,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y || 0;
                            return context.dataset.label + ': KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(148, 163, 184, .18)'
                    },
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, .18)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            elements: {
                line: {
                    borderJoinStyle: 'round'
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }
}
