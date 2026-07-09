<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ProcurementPaymentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Supplier Invoice Payment Status';

    protected static ?string $description = 'Grouped comparison of paid, partial, unpaid, and overdue supplier invoices.';

    protected static ?int $sort = 3;

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
                    'from' => $cursor->toDateString(),
                    'to' => $cursor->toDateString(),
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
        $paid = [];
        $partial = [];
        $unpaid = [];
        $overdue = [];

        foreach ($this->getBuckets() as $bucket) {
            $labels[] = $bucket['label'];

            $baseQuery = PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$bucket['from'], $bucket['to']]);

            $paid[] = (clone $baseQuery)
                ->where('payment_status', 'paid')
                ->count();

            $partial[] = (clone $baseQuery)
                ->where('payment_status', 'partial')
                ->count();

            $unpaid[] = (clone $baseQuery)
                ->where('payment_status', 'unpaid')
                ->count();

            $overdue[] = (clone $baseQuery)
                ->where('balance_due', '>', 0)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now('Africa/Nairobi')->toDateString())
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Paid',
                    'data' => $paid,
                    'backgroundColor' => 'rgba(34, 197, 94, .88)',
                    'borderColor' => '#16a34a',
                    'borderWidth' => 2,
                    'borderRadius' => 10,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Partial',
                    'data' => $partial,
                    'backgroundColor' => 'rgba(245, 158, 11, .88)',
                    'borderColor' => '#d97706',
                    'borderWidth' => 2,
                    'borderRadius' => 10,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Unpaid',
                    'data' => $unpaid,
                    'backgroundColor' => 'rgba(239, 68, 68, .88)',
                    'borderColor' => '#dc2626',
                    'borderWidth' => 2,
                    'borderRadius' => 10,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Overdue',
                    'data' => $overdue,
                    'backgroundColor' => 'rgba(168, 85, 247, .88)',
                    'borderColor' => '#9333ea',
                    'borderWidth' => 2,
                    'borderRadius' => 10,
                    'borderSkipped' => false,
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
                        padding: 14,
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, .95)',
                    padding: 12,
                    bodySpacing: 6,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y || 0;
                            return context.dataset.label + ': ' + value + ' invoice(s)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
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
                        precision: 0,
                        stepSize: 1
                    }
                }
            },
            datasets: {
                bar: {
                    barPercentage: .72,
                    categoryPercentage: .72,
                    hoverBorderWidth: 3
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
