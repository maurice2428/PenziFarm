<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class ProcurementApprovalStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Purchase Order Status';

    protected static ?string $description = 'Current procurement flow status: draft, ordered, partially received, received and cancelled.';

    protected static ?int $sort = 9;

    protected static ?string $maxHeight = '360px';

    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function range(): array
    {
        return [
            $this->dateFrom ?: now('Africa/Nairobi')->startOfMonth()->toDateString(),
            $this->dateTo ?: now('Africa/Nairobi')->endOfMonth()->toDateString(),
        ];
    }

    protected function getData(): array
    {
        [$from, $to] = $this->range();

        $statuses = [
            'draft' => 'Draft',
            'ordered' => 'Ordered',
            'partially_received' => 'Partially Received',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
        ];

        $data = [];

        foreach (array_keys($statuses) as $status) {
            $data[] = PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$from, $to])
                ->where('status', $status)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Purchase Orders',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(107, 114, 128, .90)',
                        'rgba(59, 130, 246, .90)',
                        'rgba(245, 158, 11, .90)',
                        'rgba(34, 197, 94, .90)',
                        'rgba(239, 68, 68, .90)',
                    ],
                    'borderColor' => [
                        '#4b5563',
                        '#2563eb',
                        '#d97706',
                        '#16a34a',
                        '#dc2626',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => array_values($statuses),
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
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, .96)',
                    padding: 12,
                    bodySpacing: 6,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y || 0;
                            return context.dataset.label + ': ' + value + ' order(s)';
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
                        autoSkip: false,
                        font: {
                            weight: 'bold'
                        }
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
                    barPercentage: .58,
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
