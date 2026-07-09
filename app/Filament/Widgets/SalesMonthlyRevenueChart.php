<?php

namespace App\Filament\Widgets;

use App\Models\Sales\SalesInvoice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class SalesMonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Revenue Trend';

    protected static ?string $description = 'Monthly invoice revenue for the selected period.';

    protected int | string | array $columnSpan = 'full';

    public ?array $filters = [];

    protected function getData(): array
    {
        $filters = $this->filters ?? [];

        $dateFrom = Carbon::parse(
            $filters['date_from'] ?? now('Africa/Nairobi')->startOfYear()
        )->startOfMonth();

        $dateTo = Carbon::parse(
            $filters['date_to'] ?? now('Africa/Nairobi')
        )->endOfMonth();

        $records = SalesInvoice::query()
            ->selectRaw("DATE_FORMAT(invoice_date, '%Y-%m') as month_key")
            ->selectRaw('SUM(grand_total) as total')
            ->whereBetween('invoice_date', [
                $dateFrom->toDateString(),
                $dateTo->toDateString(),
            ])
            ->when(
                $filters['payment_status'] ?? null,
                fn ($query, $status) => $query->where('payment_status', $status)
            )
            ->when(
                $filters['invoice_status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('total', 'month_key');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($dateFrom, '1 month', $dateTo) as $month) {
            $key = $month->format('Y-m');

            $labels[] = $month->format('M Y');
            $data[] = (float) ($records[$key] ?? 0);
        }

        return [
    'datasets' => [
        [
            'label' => 'Invoice Revenue',

            'data' => $data,

            'borderColor' => '#2563EB',
            'backgroundColor' => 'rgba(37,99,235,0.15)',

            'tension' => 0.35,
            'fill' => true,

            'borderWidth' => 3,

            'pointRadius' => 3,
            'pointHoverRadius' => 6,

            'pointBackgroundColor' => '#2563EB',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 2,
        ],
    ],

    'labels' => $labels,
];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],

            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],

            'scales' => [
                'x' => [
    'grid' => [
        'display' => false,
    ],

    'ticks' => [
        'autoSkip' => false,
        'maxRotation' => 45,
        'minRotation' => 45,
        'padding' => 10,
    ],

    'title' => [
        'display' => true,
        'text' => 'Month',
    ],
],

                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (KES)',
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
