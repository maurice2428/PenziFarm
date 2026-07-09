<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProcurementTopSuppliersSpendChart extends ChartWidget
{
    protected static ?string $heading = 'Top Suppliers by Spend';

    protected static ?string $description = 'Suppliers delivering the highest procurement value in the selected period.';

    protected static ?int $sort = 6;

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

        $rows = PurchaseOrder::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->whereNull('purchase_orders.deleted_at')
            ->whereBetween('purchase_orders.order_date', [$from, $to])
            ->select([
                'suppliers.company_name',
                DB::raw('SUM(purchase_orders.grand_total) as total_spend'),
            ])
            ->groupBy('suppliers.company_name')
            ->orderByDesc('total_spend')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Supplier Spend',
                    'data' => $rows->pluck('total_spend')->map(fn ($value) => round((float) $value, 2))->all(),
                    'backgroundColor' => [
                        'rgba(20, 184, 166, .90)',
                        'rgba(59, 130, 246, .90)',
                        'rgba(245, 158, 11, .90)',
                        'rgba(168, 85, 247, .90)',
                        'rgba(34, 197, 94, .90)',
                        'rgba(239, 68, 68, .90)',
                        'rgba(14, 165, 233, .90)',
                        'rgba(249, 115, 22, .90)',
                    ],
                    'borderColor' => [
                        '#0f766e',
                        '#2563eb',
                        '#d97706',
                        '#9333ea',
                        '#16a34a',
                        '#dc2626',
                        '#0284c7',
                        '#ea580c',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $rows->pluck('company_name')->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            indexAxis: 'y',
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
                            let value = context.parsed.x || 0;
                            return 'Spend: KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(148, 163, 184, .18)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            },
            datasets: {
                bar: {
                    barPercentage: .58,
                    categoryPercentage: .72
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
