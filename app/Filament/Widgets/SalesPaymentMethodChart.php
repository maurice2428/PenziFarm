<?php

namespace App\Filament\Widgets;

use App\Models\Sales\SalesPayment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class SalesPaymentMethodChart extends ChartWidget
{
   // protected static string $view = 'filament.widgets.sales-payment-method-chart';

    protected static ?string $heading = 'Payment Method Performance Trend';
    protected static ?string $description = 'Daily payment collections grouped by method.';

  protected int | string | array $columnSpan = 'full';


    public ?array $filters = [];

    protected function getMaxHeight(): ?string
    {
        return '420px';
    }

    protected function getData(): array
    {
        $filters = $this->filters ?? [];

        $dateFrom = Carbon::parse($filters['date_from'] ?? now('Africa/Nairobi')->startOfMonth());
        $dateTo = Carbon::parse($filters['date_to'] ?? now('Africa/Nairobi'));

        $methods = [
            'cash' => 'Cash',
            'mpesa_stk' => 'M-Pesa STK',
            'mpesa_paybill' => 'M-Pesa Paybill',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'other' => 'Other',
        ];

        if (!empty($filters['payment_method'])) {
            $methods = [
                $filters['payment_method'] => $methods[$filters['payment_method']] ?? str($filters['payment_method'])->replace('_', ' ')->title(),
            ];
        }

        $period = CarbonPeriod::create($dateFrom, '1 day', $dateTo);

        $labels = [];
        foreach ($period as $day) {
            $labels[] = $day->format('d M');
        }

        $datasets = [];

        foreach ($methods as $method => $label) {
            $records = SalesPayment::query()
                ->selectRaw('payment_date, SUM(amount) as total')
                ->where('status', 'successful')
                ->where('payment_method', $method)
                ->whereBetween('payment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->groupBy('payment_date')
                ->pluck('total', 'payment_date');

            $data = [];

            foreach (CarbonPeriod::create($dateFrom, '1 day', $dateTo) as $day) {
                $data[] = (float) ($records[$day->toDateString()] ?? 0);
            }

            $colors = [
    'cash' => [
        'border' => '#10B981',
        'background' => 'rgba(16,185,129,0.15)',
    ],

    'mpesa_stk' => [
        'border' => '#22C55E',
        'background' => 'rgba(34,197,94,0.15)',
    ],

    'mpesa_paybill' => [
        'border' => '#16A34A',
        'background' => 'rgba(22,163,74,0.15)',
    ],

    'bank_transfer' => [
        'border' => '#3B82F6',
        'background' => 'rgba(59,130,246,0.15)',
    ],

    'cheque' => [
        'border' => '#F59E0B',
        'background' => 'rgba(245,158,11,0.15)',
    ],

    'other' => [
        'border' => '#8B5CF6',
        'background' => 'rgba(139,92,246,0.15)',
    ],
];

$color = $colors[$method] ?? [
    'border' => '#6B7280',
    'background' => 'rgba(107,114,128,0.15)',
];

$datasets[] = [
    'label' => $label,

    'data' => $data,

    'borderColor' => $color['border'],
    'backgroundColor' => $color['background'],

    'tension' => 0.45,

    'borderWidth' => 3,

    'pointRadius' => 3,
    'pointHoverRadius' => 6,

    'pointBackgroundColor' => $color['border'],
    'pointBorderColor' => '#ffffff',
    'pointBorderWidth' => 2,

    'fill' => true,
];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                    'autoSkip' => true,
                    'maxTicksLimit' => 8,
                    'maxRotation' => 0,
                    'minRotation' => 0,
                    'padding' => 8,
                ],

                'title' => [
                    'display' => true,
                    'text' => 'Date',
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
}
