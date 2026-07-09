<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrderItem;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProcurementExpenseCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Expense by Category';

    protected static ?string $description = 'Spend breakdown by feed, vaccines, dewormers, dips, treatments and other inputs.';

    protected static ?int $sort = 7;

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

        $rows = PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'purchase_order_items.inventory_item_id')
            ->whereNull('purchase_order_items.deleted_at')
            ->whereNull('purchase_orders.deleted_at')
            ->whereBetween('purchase_orders.order_date', [$from, $to])
            ->select([
                DB::raw("COALESCE(inventory_items.category, 'other') as category"),
                DB::raw('SUM(purchase_order_items.line_total) as total_amount'),
            ])
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Category Spend',
                    'data' => $rows->pluck('total_amount')->map(fn ($value) => round((float) $value, 2))->all(),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, .90)',
                        'rgba(59, 130, 246, .90)',
                        'rgba(245, 158, 11, .90)',
                        'rgba(239, 68, 68, .90)',
                        'rgba(168, 85, 247, .90)',
                        'rgba(20, 184, 166, .90)',
                        'rgba(249, 115, 22, .90)',
                    ],
                    'borderWidth' => 4,
                    'borderColor' => '#ffffff',
                    'hoverOffset' => 12,
                ],
            ],
            'labels' => $rows->pluck('category')
                ->map(fn ($category) => str($category)->replace('_', ' ')->title()->toString())
                ->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16,
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, .96)',
                    padding: 12,
                    bodySpacing: 6,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed || 0;
                            return context.label + ': KES ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
