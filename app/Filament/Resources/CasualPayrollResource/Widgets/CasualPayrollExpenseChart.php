<?php

namespace App\Filament\Resources\CasualPayrollResource\Widgets;

use App\Models\HR\CasualPayroll;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CasualPayrollExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Casual Labour Expenses Over Time';

    protected static ?int $sort = 1;

    protected static ?string $maxHeight = '420px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '12_months';

    protected function getFilters(): ?array
    {
        $currentYear = now()->year;

        return [
            '3_months' => 'Last 3 Months',
            '6_months' => 'Last 6 Months',
            '12_months' => 'Last 12 Months',
            'this_year' => "This Year ({$currentYear})",
            'last_year' => 'Last Year',
            'all_time' => 'All Time',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '12_months';

        [$startDate, $endDate] = $this->getDateRange($filter);

        $query = CasualPayroll::query()
            ->selectRaw('DATE_FORMAT(week_start, "%Y-%m") as month_key')
            ->selectRaw('SUM(total_amount) as total')
            ->whereNull('deleted_at')
            ->whereNotNull('week_start');

        if ($startDate) {
            $query->whereDate('week_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('week_start', '<=', $endDate);
        }

        $records = $query
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $labels = [];
        $values = [];

        foreach ($this->getMonthsForFilter($filter, $records) as $month) {
            $key = $month->format('Y-m');

            $labels[] = $month->format('M Y');
            $values[] = (float) ($records->firstWhere('month_key', $key)?->total ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Casual Payroll Expenses',
                    'data' => $values,
                    'tension' => 0.35,
                    'fill' => true,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 7,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false
                },

                plugins: {
                    legend: {
                        display: true,
                        position: 'left',
                        align: 'start',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 14,

                            generateLabels: function(chart) {
                                const labels = chart.data.labels || [];
                                const dataset = chart.data.datasets[0] || {};
                                const values = dataset.data || [];

                                return labels.map(function(label, index) {
                                    const value = Number(values[index] || 0);

                                    return {
                                        text: label + ' - KES ' + value.toLocaleString(),
                                        fillStyle: chart.data.datasets[0].borderColor || '#16a34a',
                                        strokeStyle: chart.data.datasets[0].borderColor || '#16a34a',
                                        lineWidth: 2,
                                        hidden: false,
                                        index: index
                                    };
                                });
                            }
                        },

                        onClick: function(event, legendItem, legend) {
                            // Disable hiding individual months from legend click
                            return;
                        }
                    },

                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },

                            label: function(context) {
                                const value = Number(context.parsed.y || 0);
                                return ' Casual Payroll: KES ' + value.toLocaleString();
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + Number(value).toLocaleString();
                            }
                        }
                    }
                }
            }
            JS);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getDateRange(string $filter): array
    {
        return match ($filter) {
            '3_months' => [
                now()->subMonths(2)->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],

            '6_months' => [
                now()->subMonths(5)->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],

            '12_months' => [
                now()->subMonths(11)->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],

            'this_year' => [
                now()->startOfYear()->toDateString(),
                now()->endOfYear()->toDateString(),
            ],

            'last_year' => [
                now()->subYear()->startOfYear()->toDateString(),
                now()->subYear()->endOfYear()->toDateString(),
            ],

            'all_time' => [
                null,
                null,
            ],

            default => [
                now()->subMonths(11)->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],
        };
    }

    protected function getMonthsForFilter(string $filter, $records): array
    {
        if ($filter === 'all_time') {
            if ($records->isEmpty()) {
                return collect([now()->startOfMonth()])->all();
            }

            $firstMonth = Carbon::createFromFormat('Y-m', $records->first()->month_key)->startOfMonth();
            $lastMonth = Carbon::createFromFormat('Y-m', $records->last()->month_key)->startOfMonth();

            $months = [];

            while ($firstMonth->lte($lastMonth)) {
                $months[] = $firstMonth->copy();
                $firstMonth->addMonth();
            }

            return $months;
        }

        $monthsCount = match ($filter) {
            '3_months' => 3,
            '6_months' => 6,
            '12_months' => 12,
            'this_year' => now()->month,
            'last_year' => 12,
            default => 12,
        };

        $start = match ($filter) {
            'this_year' => now()->startOfYear(),
            'last_year' => now()->subYear()->startOfYear(),
            default => now()->subMonths($monthsCount - 1)->startOfMonth(),
        };

        $months = [];

        for ($i = 0; $i < $monthsCount; $i++) {
            $months[] = $start->copy()->addMonths($i);
        }

        return $months;
    }
}
